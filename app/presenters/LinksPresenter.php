<?php

namespace App\Presenters;

use App\FormHelper;
use Nette\Application\BadRequestException;
use Nette\Application\UI\Form;
use Nette\Database\Table\ActiveRow;

class LinksPresenter extends BasePresenter {

  const LINK_NOT_FOUND = 'Link not found';

  /** @var ActiveRow */
  private $linkRow;

  public function actionAll() {
    $this->userIsLogged();
  }

  public function renderAll() {
    $this->template->links = $this->linksRepository->getAll();
  }

  public function actionRemove($id) {
    $this->userIsLogged();
    $this->linkRow = $this->linksRepository->findById($id);
    if (!$this->linkRow) {
      throw new BadRequestException(self::LINK_NOT_FOUND);
    }
  }

  public function renderRemove($id) {
    $this->template->link = $this->linkRow;
  }

  public function actionEdit($id) {
    $this->userIsLogged();
    $this->linkRow = $this->linksRepository->findById($id);
    if (!$this->linkRow) {
      throw new BadRequestException(self::LINK_NOT_FOUND);
    }
    $this->getComponent(self::EDIT_FORM)->setDefaults($this->linkRow);
  }

  public function renderEdit($id) {
    $this->template->link = $this->linkRow;
  }

  protected function createComponentAddForm() {
    $form = new Form;
    $form->addText('label', 'Názov')
          ->setAttribute('placeholder', 'Mesto Spišská Nová Ves')
          ->addRule(Form::FILLED, 'Názov je povinné pole');
    $form->addText('url', 'URL adresa')
          ->setAttribute('placeholder', 'http://www.spisskanovaves.eu');
    $form->addSubmit('save', 'Uložiť');
    $form->onSuccess[] = [$this, self::SUBMITTED_ADD_FORM];
    FormHelper::setBootstrapFormRenderer($form);
    return $form;
  }

  protected function createComponentEditForm() {
    $form = new Form;
    $form->addText('label', 'Názov')
          ->setAttribute('placeholder', 'Mesto Spišská Nová Ves')
          ->addRule(Form::FILLED, 'Názov je povinné pole');
    $form->addText('url', 'URL adresa')
          ->setAttribute('placeholder', 'http://www.spisskanovaves.eu')
          ->addRule(Form::FILLED, 'URL adresa je povinné pole.');
    $form->addSubmit('save', 'Uložiť')
          ->setAttribute('class', 'btn btn-large btn-success');
    $form->onSuccess[] = [$this, self::SUBMITTED_EDIT_FORM];
    FormHelper::setBootstrapFormRenderer($form);
    return $form;
  }

  /**
   * Component for creating a remove form
   * @return Nette\Application\UI\Form
   */
  protected function createComponentRemoveForm() {
    $form = new Form;
            $form = new Form;
    $form->addSubmit('save', 'Odstrániť')
          ->setAttribute('class', self::BTN_DANGER)
          ->onClick[] = [$this, self::SUBMITTED_REMOVE_FORM];
    $form->addSubmit('cancel', 'Zrušiť')
          ->setAttribute('class', self::BTN_WARNING)
          ->onClick[] = [$this, 'formCancelled'];
    $form->addProtection(self::CSRF_TOKEN_EXPIRED);
    $form->onSuccess[] = [$this, self::SUBMITTED_REMOVE_FORM];
    FormHelper::setBootstrapFormRenderer($form);
    return $form;
  }

  public function submittedAddForm(Form $form, $values) {
    $this->linksRepository->insert($values);
    $this->flashMessage('Odkaz bol pridaný', self::SUCCESS);
    $this->redirect('all');
  }

  public function submittedEditForm(Form $form, $values) {
    $this->linkRow->update($values);
    $this->flashMessage('Odkaz bol upravený', self::SUCCESS);
    $this->redirect('all');
  }

  public function submittedRemoveForm() {
    $this->linksRepository->remove($this->linkRow);
    $this->flashMessage('Odkaz bol odstránený', self::SUCCESS);
    $this->redirect('all');
  }

  public function formCancelled() {
    $this->redirect('all');
  }

}
