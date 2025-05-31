<?php

namespace App\Livewire\Members;

use App\Models\CashRegister;
use App\Models\MembershipCard;
use App\Models\User;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class SellMembershipCard extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    public $user_id = '';
    public $id = '';
    public $users = [];
    public $cardId;
    public $editModal = false;

    public $perPage = 10;
    public $search_term;
    public $search;

    protected $listeners = ['postDeleted'];


    public function mount()
    {
        $this->fill(request()->only('search'));
        $this->search_term = '%' . $this->search . '%';
    }

    public function sellCard()
    {
        $this->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $code = $this->generateUniqueCardCode();

        $membershipCard = MembershipCard::create([
            'user_id' => $this->user_id,
            'code' => $code,
            'prix' => 1000,
        ]);

        if ($membershipCard) {
            CashRegister::create([
                'type_operation' => 'Adhésion',
                'montant' => 1000,
                'reference_type' => MembershipCard::class,
                'reference_id' => $membershipCard->id,
            ]);
        }

        $this->dispatch('fresh');
        $this->dispatch('closeModal', name: 'modalVenteCarnet');
        $this->reset('user_id');

        notyf()->success("Carnet vendu avec succès ! Code : $code");
    }

    public function updateCard()
    {

        $this->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        try {
            MembershipCard::find($this->cardId)->update([
                'user_id' =>  $this->user_id,
            ]);

            $this->editModal = false;
            $this->dispatch('closeModal', name: 'modalVenteCarnet');
            $this->reset('user_id');

            notyf()->success("Mise à jour effectuée avec succès !");
        } catch (\Throwable $th) {
            notyf()->error("Une erreur est survenue");
        }
    }

    public function closeModal()
    {
        $this->dispatch('closeModal', name: 'modalVenteCarnet');
    }

    public function openModal()
    {
        $this->reset('user_id');
        $this->dispatch('openModal', name: 'modalVenteCarnet');
    }


    public function openEditModal($id)
    {
        $this->editModal = true;
        $membershipCard = MembershipCard::find($id);

        $this->cardId = $membershipCard->id;
        $this->user_id = $membershipCard->user_id;

        $this->dispatch('openModal', name: 'modalVenteCarnet');
    }

    public function sendConfirm($id, $type, $message, $title)
    {
        $this->id = $id;

        $this->dispatch(
            'sendConfirm',
            type: $type,
            title: $title,
            message: $message,
            id: $this->id,
            action: 'sellCardAction'

        );
    }

    #[On('sellCardAction')]
    public function destroy($id)
    {
        try {
            MembershipCard::find($id)->delete();

            $cashRegister = CashRegister::find($id);

            $cashRegister->delete();

            notyf()->success("Suppression effectuée avec succès !");
            
        } catch (\Throwable $th) {
            notyf()->error("Une erreur est survenue");
        }
    }

    private function generateUniqueCardCode()
    {
        $lastCard = MembershipCard::orderByDesc('id')->first();
        $number = $lastCard ? intval(substr($lastCard->code, 4)) + 1 : 1;
        return 'CARN' . str_pad($number, 3, '0', STR_PAD_LEFT);
    }

    public function changeCategoryPerPage($pageSize)
    {
        $this->perPage = $pageSize;
    }

    public function render()
    {

        $this->users = User::where('role', 'membre')->get();
        $shipCards = MembershipCard::with('user')->orderBy('created_at', 'DESC')
            ->where('code', 'like', '%' . $this->search . '%')
            ->orWhere('prix', 'like', '%' . $this->search . '%')
            ->paginate($this->perPage);

        return view('livewire.members.sell-membership-card', ['shipCards' => $shipCards]);
    }
}
