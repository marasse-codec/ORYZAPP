<?php
namespace App\Livewire;

use Livewire\Component;
use App\Services\TextToSqlService;

class AiAssistant extends Component
{
    public string $question = '';
    public ?array $response = null;
    public ?string $errorMessage = null;

    public function processQuestion(TextToSqlService $service)
    {
        $this->reset(['response', 'errorMessage']);
        $this->validate(['question' => 'required|string|min:5']);

        try {
            $this->response = $service->ask($this->question);
        } catch (\Exception $e) {
            $this->errorMessage = "Impossible de traiter la demande : " . $e->getMessage();
        }
    }

    public function render()
    {
        return view('livewire.ai-assistant');
    }
}