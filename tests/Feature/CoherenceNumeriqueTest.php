<?php

namespace Tests\Feature;

use App\Domain\Commission\Models\Attribution;
use App\Domain\Referentiel\Models\UniteDosage;
use App\Domain\Stock\Models\Appui;
use App\Domain\Stock\Models\MouvementStock;
use App\Models\User;
use App\Utils\Helper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Tests\Concerns\CreeDonneesDeSuivi;
use Tests\TestCase;

/**
 * Quantités, prix, remises et superficies : une seule précision de la base au formulaire.
 *
 *  - quantité   DECIMAL(16,4)  (400 g d'un produit compté en tonnes = 0,0004)
 *  - prix       DECIMAL(12,2)
 *  - superficie DECIMAL(10,2)
 *  - remise     DECIMAL(5,2), de 0 à 100
 * Les équipements se comptent à l'unité.
 */
class CoherenceNumeriqueTest extends TestCase
{
    use CreeDonneesDeSuivi;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->preparerDonnees();
        Storage::fake('public');
    }

    private function urlAttribution(): string
    {
        $demande = $this->demandeSoumise();

        return route('attributions.store', ['idDemande' => $demande->id_demande, 'idSession' => $this->seance()->id_session]);
    }

    // ------------------------------------------------------------ Quantités au dix-millième

    public function test_une_quantite_de_400_grammes_en_tonnes_se_saisit_se_garde_et_sort_du_stock(): void
    {
        Appui::whereKey(1)->update(['quantite_en_stock' => 1]);
        $this->actingAs(User::role('agent-commission')->first());

        $this->post($this->urlAttribution(), ['lignes' => [['id_appui' => 1, 'quantite_attribuee' => '0.0004']]])
            ->assertSessionHasNoErrors();

        $attribution = Attribution::firstOrFail();
        $this->assertEqualsWithDelta(0.0004, (float) $attribution->lignesAttributions->first()->quantite_attribuee, 1e-9);

        // Publiée, elle se retire : le stock baisse de 0,0004 exactement, et le mouvement garde la même valeur.
        $this->publier($attribution);
        $this->actingAs(User::role('gestionnaire-stock')->first())
            ->patch(route('attributions.destocker', $attribution), [
                'date_destockage' => today()->toDateString(),
                'recipiendaire' => 'Kouassi Yao',
                'piece_identite' => UploadedFile::fake()->create('cni.pdf', 100, 'application/pdf'),
            ])->assertSessionHasNoErrors();

        $this->assertEqualsWithDelta(0.9996, (float) Appui::find(1)->quantite_en_stock, 1e-9);
        $this->assertEqualsWithDelta(0.0004, (float) MouvementStock::where('id_attribution', $attribution->id_attribution)->sole()->quantite, 1e-9);

        // Et on la lit sans bruit : « 0,0004 », pas « 0.0004 » ni « 0,00 ».
        $this->get(route('attributions.show', $attribution))->assertOk()->assertSee('0,0004');
    }

    public function test_un_consommable_se_stocke_en_decimal_mais_un_equipement_se_compte_a_lunite(): void
    {
        $stock = (float) Appui::find(1)->quantite_en_stock;
        $this->actingAs(User::role('gestionnaire-stock')->first());

        $this->post(route('approvisionnements.store'), [
            'date_approvisionnement' => today()->toDateString(),
            'lignes' => [['id_appui' => 1, 'quantite_ajoutee' => '2.5']],
        ])->assertSessionHasNoErrors();
        $this->assertEqualsWithDelta($stock + 2.5, (float) Appui::find(1)->quantite_en_stock, 1e-9);

        // Appui 3 : une charrue. 2,5 charrues n'existent pas.
        $this->post(route('approvisionnements.store'), [
            'date_approvisionnement' => today()->toDateString(),
            'lignes' => [['id_appui' => 3, 'quantite_ajoutee' => '2.5', 'matricules' => ['X-1', 'X-2']]],
        ])->assertSessionHasErrors('lignes.0.quantite_ajoutee');
    }

    public function test_une_attribution_de_materiel_exige_un_nombre_entier(): void
    {
        $this->actingAs(User::role('agent-commission')->first());
        $url = $this->urlAttribution();

        $this->post($url, ['lignes' => [['id_appui' => 3, 'quantite_attribuee' => '1.5']]])->assertSessionHasErrors('lignes.0.quantite_attribuee');
        $this->post($url, ['lignes' => [['id_appui' => 3, 'quantite_attribuee' => '2']]])->assertSessionHasNoErrors();
    }

    // ------------------------------------------------------------------------ Refus nets

    public function test_une_valeur_que_la_base_arrondirait_est_refusee_au_lieu_dun_arrondi_silencieux(): void
    {
        $this->actingAs(User::role('agent-commission')->first());
        $url = $this->urlAttribution();
        $ligne = fn (array $surcharge) => ['lignes' => [array_merge(['id_appui' => 1, 'quantite_attribuee' => 1], $surcharge)]];

        // Quantité : 5 décimales, ou trop grande pour la colonne.
        $this->post($url, $ligne(['quantite_attribuee' => '0.00004']))->assertSessionHasErrors('lignes.0.quantite_attribuee');
        $this->post($url, $ligne(['quantite_attribuee' => '1000000000000']))->assertSessionHasErrors('lignes.0.quantite_attribuee');
        // Prix : 3 décimales, ou trop grand.
        $this->post($url, $ligne(['cout_attribution' => '10.123']))->assertSessionHasErrors('lignes.0.cout_attribution');
        $this->post($url, $ligne(['cout_attribution' => '99999999999']))->assertSessionHasErrors('lignes.0.cout_attribution');
        // Remise : de 0 à 100, deux décimales.
        $this->post($url, $ligne(['remise' => '100.5']))->assertSessionHasErrors('lignes.0.remise');
        $this->post($url, $ligne(['remise' => '10.123']))->assertSessionHasErrors('lignes.0.remise');
        // Superficie : deux décimales.
        $this->post($url, $ligne([]) + ['superficies' => [1 => '1.234']])->assertSessionHasErrors('superficies.1');
    }

    public function test_le_cout_se_valide_pareil_a_la_creation_et_a_la_modification(): void
    {
        $this->actingAs(User::role('agent-commission')->first());

        // Un coût de 0 (appui offert) passe à la création...
        $this->post($this->urlAttribution(), ['lignes' => [['id_appui' => 1, 'quantite_attribuee' => 1, 'cout_attribution' => 0]]])
            ->assertSessionHasNoErrors();

        // ... et à la modification.
        $attribution = Attribution::firstOrFail();
        $this->put(route('attributions.update', $attribution->id_attribution), [
            'lignes' => [['id_appui' => 1, 'quantite_attribuee' => 1, 'cout_attribution' => 0]],
        ])->assertSessionHasNoErrors();
    }

    // -------------------------------------------------------------------------- Catalogue

    public function test_le_catalogue_accepte_des_quantites_fines_et_refuse_les_prix_a_trois_decimales(): void
    {
        $this->actingAs(User::role('gestionnaire-stock')->first());
        $donnees = fn (array $s = []) => array_merge([
            'libelle' => 'Produit fin '.uniqid(),
            'id_type_appui' => 1,
            'id_unite' => UniteDosage::value('id_unite'),
            'dose_ha' => '0.0004',
            'cout_unitaire' => '12.5',
            'seuil_alerte' => '0.5',
        ], $s);

        $this->post(route('appuis.store'), $donnees())->assertSessionHasNoErrors();
        $appui = Appui::latest('id_appui')->first();
        $this->assertEqualsWithDelta(0.0004, (float) $appui->dose_ha, 1e-9);
        $this->assertEqualsWithDelta(0.5, (float) $appui->seuil_alerte, 1e-9);

        $this->post(route('appuis.store'), $donnees(['cout_unitaire' => '12.345']))->assertSessionHasErrors('cout_unitaire');
        $this->post(route('appuis.store'), $donnees(['dose_ha' => '0.00004']))->assertSessionHasErrors('dose_ha');
    }

    // ------------------------------------------------------------------ Affichage

    public function test_laffichage_des_quantites_est_unique_sans_zeros_inutiles(): void
    {
        $this->assertSame('5', Helper::quantite('5.0000'));
        $this->assertSame('2,5', Helper::quantite(2.5));
        $this->assertSame('0,0004', Helper::quantite('0.0004'));
        $this->assertSame('12,3457', Helper::quantite('12.34567'));
        $this->assertSame("1\u{202F}200", Helper::quantite(1200));
        $this->assertSame('100', Helper::quantite('100.0000'));
        $this->assertSame('0', Helper::quantite(null));
    }

    public function test_les_sommes_de_quantites_ne_derivent_pas(): void
    {
        $this->assertSame(0.3, Helper::arrondiQuantite(0.1 + 0.2));
        $this->assertSame(0.0004, Helper::arrondiQuantite('0.00040000'));
    }

    // -------------------------------------------------------------- Garde-fou contre la dérive

    /** Tout champ numérique d'un formulaire porte une borne (max) et une précision (decimal) : plus de règle « numeric » nue. */
    public function test_chaque_champ_numerique_declare_sa_precision_et_sa_borne(): void
    {
        $motif = '/(quantite|cout|prix|montant|remise|superficie|dose|seuil)/';
        $verifies = 0;

        foreach (glob(app_path('Http/Requests/*/*.php')) as $fichier) {
            $classe = 'App\\Http\\Requests\\'.basename(dirname($fichier)).'\\'.basename($fichier, '.php');

            if (! is_subclass_of($classe, FormRequest::class)) {
                continue;
            }

            try {
                $regles = (new $classe)->rules();
            } catch (\Throwable) {
                continue;
            }

            foreach ($regles as $champ => $regle) {
                $regle = is_array($regle) ? array_filter($regle, 'is_string') : explode('|', $regle);

                if (! preg_match($motif, $champ) || ! in_array('numeric', $regle, true)) {
                    continue;
                }

                $verifies++;
                $this->assertNotEmpty(preg_grep('/^decimal:/', $regle), "{$classe} : « {$champ} » n'impose pas de nombre de décimales.");
                $this->assertNotEmpty(preg_grep('/^max:/', $regle), "{$classe} : « {$champ} » n'a pas de borne maximale.");
            }
        }

        // Le test doit réellement regarder quelque chose.
        $this->assertGreaterThanOrEqual(10, $verifies);
    }

    public function test_un_champ_quantite_refuse_du_texte(): void
    {
        $regles = (new \App\Http\Requests\Demande\StoreDemandeRequest)->rules()['lignes.*.quantite_demandee'];

        $this->assertTrue(Validator::make(['q' => '2.5'], ['q' => $regles])->passes());
        $this->assertTrue(Validator::make(['q' => 'beaucoup'], ['q' => $regles])->fails());
    }
}
