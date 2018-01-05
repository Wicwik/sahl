<?php

namespace App\Presenters;

use App\FormHelper;
use Nette\Application\BadRequestException;
use Nette\Application\UI\Form;
use Nette\Database\Table\ActiveRow;

class ArchivesPresenter extends BasePresenter {

	/** @var ActiveRow */
	private $archiveRow;

	/** @var string */
	private $error = "Archive not found";

	public function renderAll() {
		$this->template->archive = $this->archivesRepository->findAll();
		$this->template->default_img = $this->default_img;

		if ($this->user->isLoggedIn()) {
			$this->getComponent('addForm');
		}
	}

	public function actionView($id) {
		$this->archiveRow = $this->archivesRepository->findById($id);
	}

	public function renderView($id) {
		if(!$this->archiveRow) {
			throw new BadRequestException($this->error);
		}

		$this->template->archive = $this->archiveRow;

		if ($this->user->isLoggedIn()) {
			$this->getComponent("editForm")->setDefaults($this->archiveRow);
			$this->getComponent("removeForm");
		}
	}

	protected function createComponentAddForm() {
		$form = new Form;
		$form->addText('title', 'Názov')
		     ->addRule(Form::FILLED, 'Opa, názov ešte nie je vyplnený.');
		$form->addSubmit('add', 'Pridať');
		$form->onSuccess[] = [$this, 'submittedAddForm'];
		FormHelper::setBootstrapFormRenderer($form);
		return $form;
	}

	protected function createComponentEditForm() {
		$form = new Form;
		$form->addText('title', 'Názov')
		     ->addRule(Form::FILLED, 'Opa, názov ešte nie je vyplnený.');
		$form->addSubmit('edit', 'Upraviť')
			 ->setAttribute('class', 'btn btn-large btn-success');
		$form->onSuccess[] = [$this, 'submittedEditForm'];
		FormHelper::setBootstrapFormRenderer($form);
		return $form;
	}

	protected function createComponentRemoveForm() {
		$form = new Form;
        $form->addSubmit('remove', 'Odstrániť')
             ->setAttribute('class', 'btn btn-large btn-danger');
        $form->addSubmit('cancel', 'Zrušiť')
             ->setAttribute('class', 'btn btn-large btn-warning')
             ->setAttribute('data-dismiss', 'modal');
        $form->onSuccess[] = [$this, 'submittedRemoveForm'];
        $form->addProtection();
        FormHelper::setBootstrapFormRenderer($form);
        return $form;
	}

	protected function createComponentArchiveForm() {
        $form = new Form;
        $archives = $this->archiveRepository->getArchives();
        $form->addSelect('archive_id', 'Vyber archív: ', $archives);
        $form->addSubmit('save', 'Archivovať');
        $form->onSuccess[] = [$this, 'submittedArchiveForm'];
        FormHelper::setBootstrapFormRenderer($form);
        return $form;
	}

	public function submittedAddForm(Form $form, $values) {
		$this->archiveRepository->insert($values);
		$this->flashMessage("Archív bol pridaný", "success");
		$this->redirect('all');
	}

	public function submittedEditForm(Form $form, $values) {
		$this->archiveRow->update($values);
		$this->flashMessage("Archív bol upravený", "success");
		$this->redirect('view', $this->archiveRow);
	}

	public function submittedRemoveForm(Form $form) {
        $this->redirect('all');
	}

	public function submittedArchiveForm(Form $form, $values) {
		$team_id = array();
		$player_id = array();
		$arch_id = array( 'archive_id' => $values['archive_id'] );

		$rounds = $this->roundsRepository->findByValue('archive_id', null);
		$events = $this->eventsRepository->findByValue('archive_id', null);
		$rules = $this->rulesRepository->findByValue('archive_id', null);

		$this->addToArchive($rounds, $arch_id);
		$this->addToArchive($events, $arch_id);
		$this->addToArchive($rules, $arch_id);

		// Vytvoríme duplicitné záznamy tímov s novým archive id
		$teams = $this->teamsRepository->getAsArray();
		foreach ($teams as $team) {
			$data = array (
				'name' => $team->name,
				'image' => $team->image,
				'archive_id' => $values['archive_id']
			);

			$id = $this->teamsRepository->insert($data);
			if ($id == null) {
				$this->flashMessage('Nastala chyba počas archivácie tímov', 'danger');
				$this->redirect('all');
			} else {
				$team_id[$team->id] = $id;
			}
		}

		// Vytvoríme duplicitné záznamy o hráčoch s novým archive_id
		$data = array();
		$players = $this->playersRepository->getAsArray();
		foreach ($players as $player) {
			if (isset($team_id[$player->team_id])) {
				$data['team_id'] = $team_id[$player->team_id];
				$data['type_id'] = $player->type_id;
				$data['lname'] = $player->lname;
				$data['num'] = $player->num;
				$data['born'] = $player->born;
				$data['goals'] = $player->goals;
				$data['trans'] = $player->trans;
				$data['archive_id'] = $values['archive_id'];
				$this->playersRepository->insert($data);
				$player_id[$player->id] = $id;
			} else {
				$this->flashMessage('Nastala chyba počas archivácie hráčov', 'danger');
				break;
			}
		}
		
		$tables = $this->tablesRepository->findByValue('archive_id', null);
		$data = array ( 
			'team_id' => null,
			'archive_id' => $values['archive_id']
		);

		foreach ($tables as $table) {
			if (isset($team_id[$table->team_id])) {
				$data['team_id'] = $team_id[$table->team_id];
				$table->update($data);
			} else {
				$this->flashMessage('Nastala chyba počas archivácie tabuliek', 'danger');
				break;
			}
		}

		$puns = $this->punishmentsRepository->findByValue('archive_id', null);
		$data = array (
			'player_id' => null,
			'archive_id' => $values['archive_id']
		);

		foreach ($puns as $pun) {
			if (isset($player_id[$pun->player_id])) {
				$data['player_id'] = $player_id[$pun->player_id];
				$pun->update($data);
			} else {
				$this->flashMessage('Nastala chyba počas archivácii trestov hráčov', 'danger');
				break;
			}
		}

		$fights = $this->fightsRepository->findByValue('archive_id', null);
		$data = array( 
			'team1_id' => null,
			'team2_id' => null,
			'archive_id' => $values['archive_id']
		);

		foreach ($fights as $fight) {
			if (isset($team_id[$fight->team1_id]) && isset($team_id[$fight->team2_id])) {
				$data['team1_id'] = $team_id[$fight->team1_id];
				$data['team2_id'] = $team_id[$fight->team2_id];
				$fight->update($data);
			} else {
				$this->flashMessage('Nastala chyba počas archivácie výsledkov zápasov', 'danger');
				break;
			}
		}

		$goals = $this->goalsRepository->findByValue('archive_id', null);
		$data = array(
			'player_id' => null,
			'archive_id' =>  $values['archive_id']
		);

		foreach ($goals as $goal) {
			if (isset($player_id[$goal->player_id])) {
				$data['player_id'] = $player_id[$goal->player_id];
				$id = $goal->update($data);
			} else {
				$this->flashMessage('Nastala chyba počas archivácie gólov', 'danger');
				break;
			}
		}

		$this->flashMessage('Záznamy boli archivované', 'success');
		$this->redirect('all');
	}

	public function formCancelled() {
		$this->redirect('all');
	}

	private function addToArchive($items, $arch_id) {
		foreach ($items as $item) {
			$item->update($arch_id);
		}
	}
}