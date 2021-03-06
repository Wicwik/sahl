<?php

declare(strict_types = 1);

namespace App\Presenters;

use App\Forms\SignInFormFactory;
use App\Model\GroupsRepository;
use App\Model\LinksRepository;
use App\Model\SeasonsGroupsRepository;
use App\Model\SponsorsRepository;
use App\Model\SeasonsGroupsTeamsRepository;
use App\Model\TeamsRepository;
use Nette\Application\UI\Form;
use Nette\Security\AuthenticationException;
use Nette\Utils\ArrayHash;

/**
 * Class SignPresenter
 * @package App\Presenters
 */
class SignPresenter extends BasePresenter
{

  /** @var SignInFormFactory */
  private $signInFormFactory;

  /**
   * SignPresenter constructor.
   * @param LinksRepository $linksRepository
   * @param SponsorsRepository $sponsorsRepository
   * @param TeamsRepository $teamsRepository
   * @param SignInFormFactory $signInFormFactory
   * @param SeasonsGroupsTeamsRepository $seasonsGroupsTeamsRepository
   * @param GroupsRepository $groupsRepository
   * @param SeasonsGroupsRepository $seasonsGroupsRepository
   */
  public function __construct(
      LinksRepository $linksRepository,
      SponsorsRepository $sponsorsRepository,
      TeamsRepository $teamsRepository,
      SignInFormFactory $signInFormFactory,
      SeasonsGroupsTeamsRepository $seasonsGroupsTeamsRepository,
      GroupsRepository $groupsRepository,
      SeasonsGroupsRepository $seasonsGroupsRepository
  )
  {
    parent::__construct($groupsRepository, $linksRepository, $sponsorsRepository, $teamsRepository,
        $seasonsGroupsRepository, $seasonsGroupsTeamsRepository);
    $this->signInFormFactory = $signInFormFactory;
  }

  /**
   *
   */
  public function actionIn(): void
  {
    if ($this->user->isLoggedIn()) {
      $this->redirect('Homepage:all');
    }
  }

  /**
   * Component for creating a sign in form
   * @return Form
   */
  protected function createComponentSignInForm(): Form
  {
    return $this->signInFormFactory->create(function (Form $form, ArrayHash $values) {
      try {
        $this->user->login($values->username, $values->password);
        $this->flashMessage('Vitajte v administrácií SAHL', self::SUCCESS);
        $this->redirect('Homepage:all');
      } catch (AuthenticationException $e) {
        $this->flashMessage('Nesprávne meno alebo heslo', self::DANGER);
        $this->redirect('Homepage:all');
      }
    });
  }

  /**
   * Log out action routing
   */
  public function actionOut(): void
  {
    $this->getUser()->logout();
    $this->flashMessage('Boli ste odhlásený', self::SUCCESS);
    $this->redirect('Homepage:all');
  }

}
