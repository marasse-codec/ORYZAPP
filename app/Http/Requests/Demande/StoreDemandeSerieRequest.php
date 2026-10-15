<?php

namespace App\Http\Requests\Demande;

use App\Domain\Demande\Models\Acteur;

use App\Domain\Stock\Models\Appui;
use App\Http\Requests\Concerns\ReglesNumeriques;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * La saisie en série : ce qui est commun au lot, puis une ligne par acteur.
 * Les messages citent le numéro de ligne, tel que l'écran l'affiche.
 */
class StoreDemandeSerieRequest extends FormRequest
{
    /** Au-delà, la saisie se découpe : une requête de taille raisonnable, une transaction raisonnable. */
    public const MAX_ACTEURS = 200;

    /** La direction et la comptabilité consultent sans saisir de demande (cf. RoleEnum::peutSaisirDesDemandes). */
    public function authorize(): bool
    {
        return $this->user()?->roleEnum()?->peutSaisirDesDemandes() ?? true;
    }

    /** Lignes vides écartées ; téléphone ramené à ses chiffres (« 07 01 02 03 04 » devient « 0701020304 »). */
    protected function prepareForValidation(): void
    {
        $acteurs = collect($this->input('acteurs', []))
            ->map(function ($ligne) {
                $ligne = (array) $ligne;
                $ligne['telephone'] = isset($ligne['telephone']) ? preg_replace('/\D/', '', (string) $ligne['telephone']) : null;

                return $ligne;
            })
            ->filter(fn (array $ligne) => collect($ligne)->except('quantites')->filter(fn ($v) => filled($v))->isNotEmpty()
                || collect($ligne['quantites'] ?? [])->filter(fn ($v) => filled($v))->isNotEmpty())
            ->values()
            ->all();

        $this->merge(['acteurs' => $acteurs, 'appuis' => array_values($this->input('appuis', []))]);
    }

    public function rules(): array
    {
        return [
            'id_campagne' => ['required', 'exists:campagnes,id_campagne'],
            'id_type_acteur' => ['required', 'exists:types_acteurs,id_type_acteur'],
            'localites' => ['required', 'array', 'min:1'],
            'localites.*' => ['integer', 'exists:localites,id_localite'],
            'periode_debut_mise_en_place' => ['required', 'date'],
            'periode_fin_mise_en_place' => ['required', 'date', 'after:periode_debut_mise_en_place'],
            'soumettre' => ['nullable', 'boolean'],

            'appuis' => ['required', 'array', 'min:1'],
            // Une demande ne porte qu'une ligne par type d'appui.
            'appuis.*.id_type_appui' => ['required', 'distinct', 'exists:types_appuis,id_type_appui'],
            'appuis.*.id_appui' => ['nullable', 'exists:appuis,id_appui'],

            'acteurs' => ['required', 'array', 'min:1', 'max:'.self::MAX_ACTEURS],
            'acteurs.*.nom' => ['required', 'string', 'max:60'],
            'acteurs.*.date_naissance' => ['required', 'date', 'before:today'],
            'acteurs.*.telephone' => ['required', 'digits:10'],
            'acteurs.*.nom_structure' => ['nullable', 'string', 'max:150'],
            'acteurs.*.quantites.*' => ['nullable', ...ReglesNumeriques::quantite()],
            // Observations de la visite de terrain (colonne « observations » : 255 caractères).
            'acteurs.*.visite' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_campagne.required' => 'Choisissez la campagne.',
            'id_type_acteur.required' => "Choisissez le type d'acteur.",
            'localites.required' => 'Choisissez au moins une localité.',
            'localites.min' => 'Choisissez au moins une localité.',
            'periode_debut_mise_en_place.required' => 'La date de début de mise en place est obligatoire.',
            'periode_fin_mise_en_place.required' => 'La date de fin de mise en place est obligatoire.',
            'periode_fin_mise_en_place.after' => 'La date de fin doit être postérieure à la date de début.',
            'appuis.required' => 'Ajoutez au moins un appui demandé.',
            'appuis.min' => 'Ajoutez au moins un appui demandé.',
            'appuis.*.id_type_appui.required' => "Appui n°:position : choisissez le type d'appui.",
            'appuis.*.id_type_appui.distinct' => "Appui n°:position : ce type d'appui figure déjà dans la liste.",
            'acteurs.required' => 'Saisissez au moins un acteur.',
            'acteurs.min' => 'Saisissez au moins un acteur.',
            'acteurs.max' => 'Une série ne peut pas dépasser '.self::MAX_ACTEURS.' acteurs.',
            'acteurs.*.nom.required' => 'Ligne :position : le nom est obligatoire.',
            'acteurs.*.nom.max' => 'Ligne :position : le nom ne peut pas dépasser 60 caractères.',
            'acteurs.*.date_naissance.required' => 'Ligne :position : la date de naissance est obligatoire.',
            'acteurs.*.date_naissance.date' => 'Ligne :position : la date de naissance est invalide.',
            'acteurs.*.date_naissance.before' => 'Ligne :position : la date de naissance doit être passée.',
            'acteurs.*.telephone.required' => 'Ligne :position : le téléphone est obligatoire.',
            'acteurs.*.telephone.digits' => 'Ligne :position : le téléphone doit comporter 10 chiffres.',
            'acteurs.*.quantites.*.numeric' => 'Ligne :position : une quantité est invalide.',
            'acteurs.*.quantites.*.min' => 'Ligne :position : une quantité ne peut pas être négative.',
            'acteurs.*.visite.max' => 'Ligne :position : les observations de visite ne peuvent pas dépasser 255 caractères.',
        ];
    }

    /** Ce que les règles unitaires ne voient pas : les doublons dans la série et les conflits avec les acteurs déjà connus. */
    public function after(): array
    {
        return [function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $this->verifierAppuis($validator);
            $this->verifierActeurs($validator);
            $this->verifierVisites($validator);
        }];
    }

    /** On ne soumet pas sans visite de terrain : « soumettre aussitôt » exige les observations de visite de chaque ligne. */
    private function verifierVisites(Validator $validator): void
    {
        if (! $this->boolean('soumettre')) {
            return;
        }

        foreach ($this->input('acteurs') as $i => $acteur) {
            if (! filled($acteur['visite'] ?? null)) {
                $validator->errors()->add("acteurs.$i.visite", 'Ligne '.($i + 1).' : une visite de terrain doit être renseignée pour soumettre la demande.');
            }
        }
    }

    private function verifierAppuis(Validator $validator): void
    {
        foreach ($this->input('appuis') as $k => $appui) {
            if (! empty($appui['id_appui']) && Appui::whereKey($appui['id_appui'])->value('id_type_appui') != $appui['id_type_appui']) {
                $validator->errors()->add("appuis.$k.id_appui", 'Appui n°'.($k + 1).' : cet appui n\'appartient pas au type d\'appui choisi.');
            }
        }
    }

    private function verifierActeurs(Validator $validator): void
    {
        $telephones = [];
        $identites = [];

        foreach ($this->input('acteurs') as $i => $acteur) {
            $ligne = $i + 1;
            $telephone = $acteur['telephone'];
            $identite = mb_strtolower(trim($acteur['nom'])).'|'.date('Y-m-d', strtotime($acteur['date_naissance']));

            if (isset($telephones[$telephone])) {
                $validator->errors()->add("acteurs.$i.telephone", "Ligne $ligne : ce téléphone figure déjà à la ligne {$telephones[$telephone]}.");
            }
            if (isset($identites[$identite])) {
                $validator->errors()->add("acteurs.$i.nom", "Ligne $ligne : cet acteur (même nom, même date de naissance) figure déjà à la ligne {$identites[$identite]}.");
            }
            $telephones[$telephone] ??= $ligne;
            $identites[$identite] ??= $ligne;

            // Un téléphone identifie un acteur : s'il appartient à quelqu'un d'autre, on ne peut pas le réaffecter.
            $connu = Acteur::where('nom', $acteur['nom'])->whereDate('date_naissance', $acteur['date_naissance'])->first();
            $titulaire = Acteur::where('telephone', $telephone)->first();

            if ($titulaire && (! $connu || $connu->id_acteur !== $titulaire->id_acteur)) {
                $validator->errors()->add("acteurs.$i.telephone", "Ligne $ligne : ce téléphone appartient déjà à un autre acteur ({$titulaire->nom}).");
            }
        }
    }
}
