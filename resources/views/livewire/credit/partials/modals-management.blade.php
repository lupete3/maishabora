<!-- Modal Modification Solde -->
<div class="modal fade @if($openModalConfirm) show @endif" id="modalModifyBalance" tabindex="-1"
    style="@if($openModalConfirm) display: block; background: rgba(0,0,0,0.5); @else display: none; @endif">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Confirmation remboursement</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Voulez-vous appliquer les intérêts futurs sur ce remboursement ?</p>
                <div>
                    <label>Penalités à payer : </label>
                    <input type="number" class="form-control" value="{{ number_format((float) $penality, 2, '.', '') }}"
                        wire:model="penality">
                </div>
            </div>
            <div class="modal-footer">
                <button wire:click="payRepayment(false)" class="btn btn-warning" data-bs-dismiss="modal">
                    Non, solder sans intérêts
                </button>
                <button wire:click="payRepayment(true)" class="btn btn-success" data-bs-dismiss="modal">
                    Oui, appliquer les intérêts
                </button>
                <button type="button" class="btn btn-secondary"
                    wire:click="$set('openModalConfirm', false)">Annuler</button>
            </div>
        </div>
    </div>
</div>
