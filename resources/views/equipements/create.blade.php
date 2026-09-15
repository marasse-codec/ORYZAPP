<form action="{{ route('equipements.retour') }}" method="POST">
    @csrf
    
    {{-- ID de l'équipement concerné --}}
    <input type="hidden" name="id_equipement" value="{{ $equipement->id_equipement }}">
    
    {{-- ID du type de mouvement (à remplacer par votre ID réel pour le type 'Retour') --}}
    <input type="hidden" name="id_type_mouvement_stock" value="2"> 

    <div class="form-group">
        <label for="etat">État de l'équipement au retour :</label>
        <select name="etat" id="etat" class="form-control" required>
            <option value="Bon état">Bon état</option>
            <option value="Abîmé">Abîmé / À réparer</option>
            <option value="Défectueux">Défectueux / Hors d'usage</option>
        </select>
    </div>

    <div class="form-group">
        <label for="id_agent">Agent qui restitue le matériel (optionnel) :</label>
        <select name="id_agent" id="id_agent" class="form-control">
            <option value="">Sélectionner un agent</option>
            @foreach($agents as $agent)
                <option value="{{ $agent->id_agent }}">{{ $agent->nom }}</option>
            @endforeach
        </select>
    </div>

    <button type="submit" class="btn btn-primary">Enregistrer le retour</button>
</form>
