<?php

namespace App\Domain\Suivi\Exports;

use App\Domain\Acces\Enums\RoleEnum;
use App\Domain\Commission\Enums\StatutAttributionEnum;
use App\Domain\Commission\Models\Attribution;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AttributionsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $dateDebut;
    protected $dateFin;
    protected array $colonnesSelectionnees;

    // Catalogue complet des colonnes disponibles
    public static function catalogueColonnes(): array
    {
        if(auth()->user()->hasRole(RoleEnum::dp->value)){
            return [
                'id_attribution' => 'ID Attribution',
                'code_demande' => 'N° Demande',
                'beneficiaire' => 'Bénéficiaire'
            ];
        }

        return [
            'id_attribution' => 'ID Attribution',
            'code_demande' => 'N° Demande',
            'superficie_totale' => 'Superficie totale (ha)',
            'service_nom' => 'Service Traitant',
            'beneficiaire' => 'Bénéficiaire',
            'appui_libelle' => 'Appui Attribué',
            'quantite_attribuee' => 'Quantité Attribuée',
            'cout_attribution' => 'Coût Attribution (FCFA)',
            'systemes_production' => 'Détail Systèmes de Production (ha)',
            'statut' => 'Statut',
            'created_at' => 'Date d\'Attribution',
        ];
    }

    public function __construct($dateDebut = null, $dateFin = null, array $colonnesSelectionnees = [])
    {
        $this->dateDebut = $dateDebut;
        $this->dateFin = $dateFin;
        // Si aucune colonne n'est choisie, on exporte toutes les colonnes par défaut
        $this->colonnesSelectionnees = !empty($colonnesSelectionnees) 
            ? $colonnesSelectionnees 
            : array_keys(self::catalogueColonnes());
    }

    public function collection(): Collection
    {
        $dateDebut = $this->dateDebut ? Carbon::parse($this->dateDebut)->startOfDay() : now()->startOfYear();
        $dateFin = $this->dateFin ? Carbon::parse($this->dateFin)->endOfDay() : now()->endOfDay();

        $selects = [
            'attributions.id_attribution',
            'demandes.id_demande as code_demande',
            'services.lib_service as service_nom',
            'acteurs.nom as beneficiaire',
            'appuis.libelle as appui_libelle',
            'lignes_attributions.quantite_attribuee',
            'lignes_attributions.cout_attribution',
            // Le statut d'une attribution n'est pas stocké : il se déduit (cf. Attribution::statutSql).
            DB::raw(Attribution::statutSql().' as statut_code'),
            'attributions.created_at',
            DB::raw("SUM(systemes_productions.superficie) as superficie_totale_demande")
        ];

        // Colonnes dynamiques pour les systèmes de production
        if (in_array('systemes_production', $this->colonnesSelectionnees)) {
            $typesSystemes = DB::table('types_systemes_productions')->get();
            foreach ($typesSystemes as $type) {
                $idType = (int) $type->id_type_systeme_production;
                $selects[] = DB::raw("SUM(CASE WHEN types_systemes_productions.id_type_systeme_production = {$idType} THEN systemes_productions.superficie ELSE 0 END) as sys_type_{$idType}");
            }
        }

        return DB::table('attributions')
            ->leftJoin('lignes_attributions', 'attributions.id_attribution', '=', 'lignes_attributions.id_attribution')
            ->leftJoin('appuis', 'lignes_attributions.id_appui', '=', 'appuis.id_appui')
            ->leftJoin('demandes', 'attributions.id_demande', '=', 'demandes.id_demande')
            ->leftJoin('systemes_productions', 'demandes.id_demande', '=', 'systemes_productions.id_demande')
            ->leftJoin('types_systemes_productions', 'systemes_productions.id_type_systeme_production', '=', 'types_systemes_productions.id_type_systeme_production')
            ->leftJoin('acteurs', 'demandes.id_acteur', '=', 'acteurs.id_acteur')
            // Le service traitant est celui qui fournit l'appui attribué.
            ->leftJoin('types_appuis', 'appuis.id_type_appui', '=', 'types_appuis.id_type_appui')
            ->leftJoin('services', 'types_appuis.id_service', '=', 'services.id_service')
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
                'attributions.date_destockage',
                'attributions.date_publication',
                'attributions.created_at'
            )
            ->get();
    }

    public function headings(): array
    {
        $headers = [];
        $catalogue = self::catalogueColonnes();

        foreach ($this->colonnesSelectionnees as $col) {
            if ($col === 'systemes_production') {
                $typesSystemes = DB::table('types_systemes_productions')->pluck('lib_type_systeme_production')->toArray();
                foreach ($typesSystemes as $libelle) {
                    $headers[] = 'Sup. ' . $libelle . ' (ha)';
                }
            } elseif (isset($catalogue[$col])) {
                $headers[] = $catalogue[$col];
            }
        }

        return $headers;
    }

    public function map($row): array
    {
        $data = [];

        foreach ($this->colonnesSelectionnees as $col) {
            switch ($col) {
                case 'id_attribution':
                    $data[] = 'ATTR-' . str_pad($row->id_attribution, 3, '0', STR_PAD_LEFT);
                    break;
                case 'code_demande':
                    $data[] = $row->code_demande;
                    break;
                case 'superficie_totale':
                    $data[] = (float) $row->superficie_totale_demande;
                    break;
                case 'service_nom':
                    $data[] = $row->service_nom ?? 'Non renseigné';
                    break;
                case 'beneficiaire':
                    $data[] = $row->beneficiaire ?? 'N/A';
                    break;
                case 'appui_libelle':
                    $data[] = $row->appui_libelle ?? 'N/A';
                    break;
                case 'quantite_attribuee':
                    $data[] = (int) $row->quantite_attribuee;
                    break;
                case 'cout_attribution':
                    $data[] = number_format((float) $row->cout_attribution, 0, ',', ' ');
                    break;
                case 'systemes_production':
                    $typesSystemes = DB::table('types_systemes_productions')->get();
                    foreach ($typesSystemes as $type) {
                        $colKey = 'sys_type_' . $type->id_type_systeme_production;
                        $data[] = (float) ($row->$colKey ?? 0);
                    }
                    break;
                case 'statut':
                    $data[] = mb_strtoupper(StatutAttributionEnum::from((int) $row->statut_code)->libelle());
                    break;
                case 'created_at':
                    $data[] = date('d/m/Y H:i', strtotime($row->created_at));
                    break;
            }
        }

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
}