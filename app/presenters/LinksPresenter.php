<?php

namespace App\Presenters;

use App\FormHelper;
use Nette\Application\BadRequestException;
use Nette\Application\UI\Form;
use Nette\Database\Table\ActiveRow;
use Nette\Utils\FileSystem;

class LinksPresenter extends BasePresenter {

    const LINK_NOT_FOUND = 'Link not found';

    /** @var ActiveRow */
    private $linkRow;

    public function actionAll() {
        $this->userIsLogged();
    }

    public function renderAll() {
        $this->template->links = $this->linksRepository->findByValue('sponsor', 0);
        $this->template->sponsors = $this->linksRepository->findByValue('sponsor', 1);
        $this->getComponent(self::ADD_FORM);
    }

    public function actionRemove($id) {
        $this->userIsLogged();
        $this->linkRow = $this->linksRepository->findById($id);
    }

    public function renderRemove($id) {
        if (!$this->linkRow) {
            throw new BadRequestException(self::LINK_NOT_FOUND);
        }
        $this->getComponent(self::REMOVE_FORM);
    }

    public function actionEdit($id) {
        $this->userIsLogged();
        $this->linkRow = $this->linksRepository->findById($id);
    }

    public function renderEdit($id) {
        if (!$this->linkRow) {
            throw new BadRequestException(self::LINK_NOT_FOUND);
        }
        $this->getComponent(self::EDIT_FORM)->setDefaults($this->linkRow);
    }

    protected function createComponentAddForm() {
        $form = new Form;
        $form->addText('title', 'Názov')
             ->setAttribute('placeholder', 'Mesto Spišská Nová Ves')
             ->addRule(Form::FILLED, 'Názov je povinné pole');
        $form->addText('anchor', 'URL adresa')
             ->setAttribute('placeholder', 'http://www.spisskanovaves.eu')
             ->addRule(Form::FILLED, 'URL adresa je povinné pole.');
        $form->addUpload('image', 'Obrázok');
        $form->addCheckbox('sponsor', ' Označiť ako sponzora');
        $form->addSubmit('save', 'Uložiť');
        $form->onSuccess[] = [$this, self::SUBMITTED_ADD_FORM];
        FormHelper::setBootstrapFormRenderer($form);
        return $form;
    }

    protected function createComponentEditForm() {
        $form = new Form;
        $form->addText('title', 'Názov')
             ->setAttribute('placeholder', 'Mesto Spišská Nová Ves')
             ->addRule(Form::FILLED, 'Názov je povinné pole');
        $form->addText('anchor', 'URL adresa')
             ->setAttribute('placeholder', 'http://www.spisskanovaves.eu')
             ->addRule(Form::FILLED, 'URL adresa je povinné pole.');
        $form->addSubmit('save', 'Uložiť');
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
        $img = $values['image'];

        $name = strtolower($img->getSanitizedName());
        try {
            if ($img->isOk() AND $img->isImage()) {
                $img->move($this->imageDir . '/' . $name);
            }
            $values['image'] = $name;
            $this->linksRepository->insert($values);
            $this->flashMessage('Odkaz bol pridaný', self::SUCCESS);
        } catch (IOException $e) {
            $this->flashMessage('Obrázok ' . $name . ' sa nepodarilo nahrať', self::DANGER);
        }

        $this->redirect('all');
    }

    public function submittedRemoveForm() {
        $image = $this->linkRow->image;
        if ($image) {
            try {
                FileSystem::delete($this->imageDir . $image);
            } catch (IOException $e) {
                $this->flashMessage('Obrázok ' . $image . ' sa nepodarilo odstrániť', self::DANGER);
            }
        }
        $this->linkRow->delete();
        $this->flashMessage('Odkaz bol odstránený', self::SUCCESS);
        $this->redirect('all');
    }

    public function submittedEditForm(Form $form, $values) {
        $this->linkRow->update($values);
        $this->flashMessage('Odkaz bol upravený', self::SUCCESS);
        $this->redirect('all');
    }

    public function formCancelled() {
        $this->redirect('all');
    }

}
