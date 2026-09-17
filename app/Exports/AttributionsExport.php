<?php

namespace App\Exports;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AttributionsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $dateDebut;
    protected $dateFin;

    public function __construct($dateDebut = null, $dateFin = null)
    {
        $this->dateDebut = $dateDebut;
        $this->dateFin = $dateFin;
    }

    public function collection(): Collection
    {
        $dateDebut = $this->dateDebut ? Carbon::parse($this->dateDebut)->startOfDay() : now()->startOfYear();
        $dateFin = $this->dateFin ? Carbon::parse($this->dateFin)->endOfDay() : now()->endOfDay();

        $typesSystemes = DB::table('types_systemes_productions')->get();

        $selects = [
            'attributions.id_attribution',
            'demandes.id_demande as code_demande',
            'services.lib_service as service_nom',
            'acteurs.nom as beneficiaire',
            'appuis.libelle as appui_libelle',
            'lignes_attributions.quantite_attribuee',
            'lignes_attributions.cout_attribution',
            'attributions.id_statut_attribution',
            'attributions.created_at',
            DB::raw("SUM(systemes_productions.superficie) as superficie_totale_demande")
        ];

        foreach ($typesSystemes as $type) {
            $idType = (int) $type->id_type_systeme_production;
            $selects[] = DB::raw("SUM(CASE WHEN types_systemes_productions.id_type_systeme_production = {$idType} THEN systemes_productions.superficie ELSE 0 END) as sys_type_{$idType}");
        }

        return DB::table('attributions')
            ->leftJoin('lignes_attributions', 'attributions.id_attribution', '=', 'lignes_attributions.id_attribution')
            ->leftJoin('appuis', 'lignes_attributions.id_appui', '=', 'appuis.id_appui')
            ->leftJoin('demandes', 'attributions.id_demande', '=', 'demandes.id_demande')
            ->leftJoin('systemes_productions', 'demandes.id_demande', '=', 'systemes_productions.id_demande')
            ->leftJoin('types_systemes_productions', 'systemes_productions.id_type_systeme_production', '=', 'types_systemes_productions.id_type_systeme_production')
            ->leftJoin('acteurs', 'demandes.id_acteur', '=', 'acteurs.id_acteur')
            ->leftJoin('traitements', 'attributions.id_traitement', '=', 'traitements.id_traitement')
            ->leftJoin('services', 'traitements.id_service', '=', 'services.id_service')
            ->select($selects)
            ->whereBetween('attributions.created_at', [$dateDebut, $dateFin])
            ->groupBy(
                'attributions.id_attribution',
                'lignes_attributions.id_ligne_attribution',
                'demandes.id_demande',
                'services.lib_service',
                'acteurs.nom',
                'appuis.libelle',
                'lignes_attributions.quantite_attribuee',
                'lignes_attributions.cout_attribution',
                'attributions.id_statut_attribution',
                'attributions.created_at'
            )
            ->get();
    }

    public function headings(): array
    {
        $typesSystemes = DB::table('types_systemes_productions')->pluck('lib_type_systeme_production')->toArray();

        $headers = [
            'ID Attribution',
            'N° Demande',
            'Superficie totale (ha)',
            'Service Traitant',
            'Bénéficiaire',
            'Appui Attribué',
            'Quantité Attribuée',
            'Coût Attribution (FCFA)',
        ];

        // En-têtes dynamiques pour chaque système de production
        foreach ($typesSystemes as $libelle) {
            $headers[] = 'Sup. ' . $libelle . ' (ha)';
        }

        $headers[] = 'Statut';
        $headers[] = 'Date d\'Attribution';

        return $headers;
    }

    public function map($row): array
    {
        $typesSystemes = DB::table('types_systemes_productions')->get();

        $statuts = [
            1 => 'Brouillon',
            2 => 'Soumise',
            3 => 'Validée',
            4 => 'Annulée',
        ];

        $statutLibelle = $statuts[$row->id_statut_attribution] ?? 'Statut ' . $row->id_statut_attribution;

        $data = [
            'ATTR-' . $row->id_attribution,
            $row->code_demande,
            (float) $row->superficie_totale_demande,
            $row->service_nom ?? 'Non renseigné',
            $row->beneficiaire ?? 'N/A',
            $row->appui_libelle ?? 'N/A',
            (int) $row->quantite_attribuee,
            number_format((float) $row->cout_attribution, 0, ',', ' '),
        ];

        // Insertion des colonnes dynamiques
        foreach ($typesSystemes as $type) {
            $colKey = 'sys_type_' . $type->id_type_systeme_production;
            $data[] = (float) ($row->$colKey ?? 0);
        }

        $data[] = strtoupper($statutLibelle);
        $data[] = date('d/m/Y H:i', strtotime($row->created_at));

        return $data;
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => '047857']
                ]
            ],
        ];
    }

            public function exporterAttributions(Request $request)
    {
        $dateDebut = $this->dateDebut ? Carbon::parse($this->dateDebut)->startOfDay() : now()->startOfYear();
        $dateFin = $this->dateFin ? Carbon::parse($this->dateFin)->endOfDay() : now()->endOfDay();
        $nomFichier = 'Varyza_Attributions_' . now()->format('Y_m_d_His') . '.xlsx';

        return Excel::download(new AttributionsExport($dateDebut, $dateFin), $nomFichier);
    }
}