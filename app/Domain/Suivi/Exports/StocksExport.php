<?php

namespace App\Domain\Suivi\Exports;

use App\Utils\Helper;

use App\Domain\Acces\Enums\RoleEnum;
use App\Domain\Commission\Services\SyntheseAttributionService;
use App\Domain\Stock\Models\Appui;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * L'état des stocks des appuis qu'un service fournit : une ligne par appui, avec le stock physique,
 * ce que les attributions non retirées ont déjà engagé, et ce qui reste disponible.
 *
 * L'agent de service est cantonné à son service ; les autres rôles autorisés peuvent restreindre à un service.
 */
class StocksExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected array $colonnesSelectionnees;

    /** @var Collection<int, int|float>|null id_appui => quantité engagée */
    private ?Collection $engagees = null;

    public static function catalogueColonnes(): array
    {
        return [
            'appui' => 'Appui',
            'type_appui' => "Type d'appui",
            'service' => 'Service fournisseur',
            'unite' => 'Unité',
            'stock' => 'Stock physique',
            'engagee' => 'Engagé (attributions non retirées)',
            'disponible' => 'Disponible',
            'seuil_alerte' => "Seuil d'alerte",
            'situation' => 'Situation',
            'cout_unitaire' => 'Coût unitaire (FCFA)',
            'valeur_stock' => 'Valeur du stock (FCFA)',
        ];
    }

    public function __construct(protected ?int $idService = null, array $colonnesSelectionnees = [])
    {
        $valides = array_values(array_intersect($colonnesSelectionnees, array_keys(self::catalogueColonnes())));
        $this->colonnesSelectionnees = $valides ?: array_keys(self::catalogueColonnes());
    }

    /**
     * Les appuis visibles : ceux du service de l'agent de service, sinon tous
     * (ou ceux du service demandé).
     */
    public static function perimetre(?int $idService = null): Builder
    {
        $user = auth()->user();

        if ($user->hasRole(RoleEnum::agentService->value)) {
            $idService = $user->id_service;
        }

        return Appui::query()
            ->when($idService, fn ($q) => $q->whereHas('typeAppui', fn ($t) => $t->where('id_service', $idService)),
                // Un agent de service sans service ne voit rien plutôt que tout.
                fn ($q) => $user->hasRole(RoleEnum::agentService->value) ? $q->whereRaw('1 = 0') : $q);
    }

    public function collection(): Collection
    {
        $appuis = self::perimetre($this->idService)
            ->with(['typeAppui.service', 'uniteDosage'])
            ->orderBy('libelle')
            ->get();

        $this->engagees = app(SyntheseAttributionService::class)
            ->quantitesEngagees($appuis->pluck('id_type_appui')->unique()->values());

        return $appuis;
    }

    public function headings(): array
    {
        $catalogue = self::catalogueColonnes();

        return array_map(fn (string $col) => $catalogue[$col], $this->colonnesSelectionnees);
    }

    /** @param  Appui  $appui */
    public function map($appui): array
    {
        $stock = $appui->quantiteeEnStock();
        $engagee = Helper::arrondiQuantite($this->engagees[$appui->id_appui] ?? 0);

        // Seuil à 0 = pas d'alerte voulue pour cet appui (cf. StatistiqueService::stock).
        $enAlerte = $appui->seuil_alerte > 0 && $stock <= $appui->seuil_alerte;

        return array_map(fn (string $col) => match ($col) {
            'appui' => $appui->libelle,
            'type_appui' => $appui->typeAppui?->lib_type_appui,
            'service' => $appui->typeAppui?->service?->lib_service,
            'unite' => $appui->uniteDosage?->lib_unite,
            'stock' => $stock,
            'engagee' => $engagee,
            'disponible' => Helper::arrondiQuantite($stock - $engagee),
            'seuil_alerte' => Helper::arrondiQuantite($appui->seuil_alerte),
            'situation' => $enAlerte ? 'EN ALERTE' : 'Normal',
            'cout_unitaire' => (float) $appui->cout_unitaire,
            'valeur_stock' => round((float) $appui->cout_unitaire * $stock, 2),
        }, $this->colonnesSelectionnees);
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
