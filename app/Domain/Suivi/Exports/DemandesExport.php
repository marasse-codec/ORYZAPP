<?php

namespace App\Domain\Suivi\Exports;

use App\Domain\Acces\Enums\RoleEnum;
use App\Domain\Demande\Models\Demande;
use App\Domain\Referentiel\Models\TypeSystemeProduction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * L'export Excel des demandes : une ligne par demande, dans le périmètre de l'utilisateur
 * (le DP ne voit que les siennes, cf. Demande::scopePourUtilisateur pour les autres rôles).
 */
class DemandesExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected array $colonnesSelectionnees;

    /** @var Collection<int, TypeSystemeProduction>|null */
    private ?Collection $ecologies = null;

    public static function catalogueColonnes(): array
    {
        return [
            'numero' => 'N° Demande',
            'date_demande' => 'Date de la demande',
            'date_soummission' => 'Date de soumission',
            'demandeur' => 'Demandeur',
            'type_acteur' => "Type d'acteur",
            'campagne' => 'Campagne',
            'localites' => 'Localité(s)',
            'appuis_demandes' => 'Appuis sollicités',
            'superficie_totale' => 'Superficie totale (ha)',
            'systemes_production' => 'Détail systèmes de production (ha)',
            'statut' => 'Statut',
            'motifs_rejet' => 'Motifs du rejet',
        ];
    }

    public function __construct(protected $dateDebut = null, protected $dateFin = null, array $colonnesSelectionnees = [])
    {
        // Sans choix, toutes les colonnes ; une clé inconnue est ignorée.
        $valides = array_values(array_intersect($colonnesSelectionnees, array_keys(self::catalogueColonnes())));
        $this->colonnesSelectionnees = $valides ?: array_keys(self::catalogueColonnes());
    }

    public function collection(): Collection
    {
        $debut = $this->dateDebut ? Carbon::parse($this->dateDebut)->startOfDay() : now()->startOfYear();
        $fin = $this->dateFin ? Carbon::parse($this->dateFin)->endOfDay() : now()->endOfDay();

        return self::perimetre()
            ->with(['acteur', 'typeActeur', 'campagne', 'localites', 'lignes.typeAppui', 'systemesProductions'])
            ->whereDate('date_demande', '>=', $debut->toDateString())
            ->whereDate('date_demande', '<=', $fin->toDateString())
            ->orderByDesc('id_demande')
            ->get();
    }

    /** Les demandes que l'utilisateur connecté a le droit de voir. */
    public static function perimetre(): Builder
    {
        $user = auth()->user();

        return $user->hasRole(RoleEnum::dp->value)
            ? Demande::query()->where('id_user', $user->id)
            : Demande::query()->pourUtilisateur();
    }

    private function ecologies(): Collection
    {
        return $this->ecologies ??= TypeSystemeProduction::orderBy('id_type_systeme_production')->get();
    }

    public function headings(): array
    {
        $catalogue = self::catalogueColonnes();
        $entetes = [];

        foreach ($this->colonnesSelectionnees as $col) {
            if ($col === 'systemes_production') {
                foreach ($this->ecologies() as $type) {
                    $entetes[] = 'Sup. '.$type->lib_type_systeme_production.' (ha)';
                }
            } else {
                $entetes[] = $catalogue[$col];
            }
        }

        return $entetes;
    }

    /** @param  Demande  $demande */
    public function map($demande): array
    {
        $ligne = [];

        foreach ($this->colonnesSelectionnees as $col) {
            match ($col) {
                'numero' => $ligne[] = 'DEM-'.str_pad($demande->id_demande, 3, '0', STR_PAD_LEFT),
                'date_demande' => $ligne[] = $demande->date_demande?->format('d/m/Y'),
                'date_soummission' => $ligne[] = $demande->date_soummission?->format('d/m/Y'),
                'demandeur' => $ligne[] = $demande->nom_structure ?? $demande->acteur?->nom ?? 'N/A',
                'type_acteur' => $ligne[] = $demande->typeActeur?->lib_type_acteur,
                'campagne' => $ligne[] = $demande->campagne?->lib_campagne,
                'localites' => $ligne[] = $demande->localites->pluck('lib_localite')->join(', '),
                'appuis_demandes' => $ligne[] = $demande->lignes->map(fn ($l) => $l->typeAppui?->lib_type_appui)->filter()->join(', '),
                'superficie_totale' => $ligne[] = (float) $demande->systemesProductions->sum('superficie'),
                'systemes_production' => array_push($ligne, ...$this->ecologies()->map(
                    fn ($type) => (float) $demande->systemesProductions->firstWhere('id_type_systeme_production', $type->id_type_systeme_production)?->superficie
                )->all()),
                'statut' => $ligne[] = mb_strtoupper($demande->statut->libelle()),
                'motifs_rejet' => $ligne[] = $demande->motifs_rejet,
            };
        }

        return $ligne;
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => '047857'],
                ],
            ],
        ];
    }
}
