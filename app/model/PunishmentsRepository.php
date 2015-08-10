<?php

namespace App\Model;

use Nette\Database\Table\ActiveRow;

class PunishmentsRepository extends Repository {

    /**
     * Finds punishments for specified player
     * @param $id holds the player id
     */
    public function getPunishmentsForPlayer($id) {
        $punishments = $this->findByValue('team_id', $id);
        return $punishments;
    }

}