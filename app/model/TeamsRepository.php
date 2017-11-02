<?php

namespace App\Model;

class TeamsRepository extends Repository {
    /**
     * Loop trought all teams and store them in array. 
     * Accessible index $team->id. 
     * Value is $team->name.
     * @return array
     */
    public function getTeams() {
        return $this->findByValue('archive_id', null)->order('name ASC')->fetchPairs('id', 'name');
    }
}
