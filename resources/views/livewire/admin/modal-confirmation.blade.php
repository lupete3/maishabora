<div class="modal fade @if($attributes->wire('model')->value) show d-block @endif"
     tabindex="-1" @if($attributes->wire('model')->value) style="background:rgba(0,0,0,0.4);" @endif>
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-warning text-dark rounded-top-4">
                <h5 class="modal-title fw-bold">{{ $title }}</h5>
                <button type="button" class="btn-close"
                    wire:click="$set('{{ $attributes->wire('model')->value }}', false)"></button>
            </div>
            <div class="modal-body">
                <p class="fw-semibold mb-3">{{ $message }}</p>
                @if(isset($details) && count($details))
                    <ul class="list-group list-group-flush mb-3">
                        @foreach ($details as $label => $value)
                            @if($value)
                                <li class="list-group-item d-flex justify-content-between">
                                    <span>{{ $label }} :</span>
                                    <span class="fw-bold">{{ $value }}</span>
                                </li>
                            @endif
                        @endforeach
                    </ul>
                @endif
            </div>
            <div class="modal-footer d-flex justify-content-between">
                <button class="btn btn-secondary"
                    wire:click="$set('{{ $attributes->wire('model')->value }}', false)">
                    Annuler
                </button>
                <button class="btn btn-success" wire:click="{{ $confirmAction }}">
                    Confirmer
                </button>
            </div>
        </div>
    </div>
</div>
