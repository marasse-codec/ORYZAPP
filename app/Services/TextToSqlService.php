<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Exception;

class TextToSqlService
{
    protected string $apiKey;
    protected string $schemaContext;

    public function __construct()
    {
        $this->apiKey = config('services.gemini.key');

        if (empty($this->apiKey)) {
            throw new Exception("La clé GEMINI_API_KEY est manquante dans votre .env.");
        }

        $schemaPath = storage_path('app/ai/schema_context.txt');
        if (!file_exists($schemaPath)) {
            throw new Exception("Le fichier de dictionnaire de données est introuvable : {$schemaPath}");
        }

        $this->schemaContext = file_get_contents($schemaPath);
    }

    public function ask(string $userQuestion): array
    {
        $sqlQuery = $this->generateSql($userQuestion);

        if (!str_starts_with(strtoupper(trim($sqlQuery)), 'SELECT')) {
            throw new Exception("Seules les requêtes SELECT sont autorisées.");
        }

        $results = DB::connection('mysql_readonly')->select($sqlQuery);
        $summary =$this->generateSummary($userQuestion,$results);

        return [
            'sql' => $sqlQuery,
            'data' => $results,
            'summary' => $summary,
        ];
    }

    protected function generateSql(string $question): string
    {
        $prompt = "Tu es un expert SQL MySQL. Génère uniquement la requête SQL brute sans bloc Markdown ni explications.\n\nContext de la BDD:\n" . $this->schemaContext . "\n\nQuestion:\n" . $question;

        $response = Http::timeout(30)->post($this->getApiUrl(), [
            'contents' => [
                ['parts' => [['text' => $prompt]]]
            ],
            'generationConfig' => [
                'temperature' => 0.1,
            ]
        ]);

        if ($response->failed()) {
            throw new Exception("Erreur Gemini : " . $response->json('error.message', 'Connexion impossible'));
        }

        $sql =$response->json('candidates.0.content.parts.0.text');

        // Nettoyage au cas où Gemini ajoute des balises ```sql
        return trim(str_replace(['```sql', '```'], '', $sql));
    }

    protected function generateSummary(string $question, array $results): string
    {
        $jsonResults = json_encode($results);
        $prompt = "Rédige une synthèse claire en 2 phrases max en français pour un décideur.\nQuestion: {$question}\nRésultats: {$jsonResults}";

        $response = Http::timeout(30)->post($this->getApiUrl(), [
            'contents' => [
                ['parts' => [['text' => $prompt]]]
            ],
        ]);

        if ($response->failed()) {
            throw new Exception("Erreur Gemini (Synthèse) : " . $response->json('error.message', 'Connexion impossible'));
        }

        return $response->json('candidates.0.content.parts.0.text', 'Analyse terminée.');
    }

    /**
     * Génère l'URL d'API v1 dynamique selon le modèle configuré
     */
    private function getApiUrl(): string
{
    $model = config('services.gemini.model') ?: 'gemini-2.0-flash';
    
    // Nettoyage des éventuels espaces ou caractères invisibles
    $model = trim($model);
    $apiKey = trim($this->apiKey);

    return "https://generativelanguage.googleapis.com/v1/models/{$model}:generateContent?key={$apiKey}";
}
}