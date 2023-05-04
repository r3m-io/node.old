<?php

namespace R3m\Io\Node\Model;

class Role {

    private $uuid;
    private $name;
    private $rank;

    public function __construct(){

    }

    public function uuid($uuid=null): string
    {
        if($uuid !== null){
            $this->setUuid($uuid);
        }
        return $this->getUuid();
    }

    private function setUuid($uuid=''): void
    {
        $this->uuid = $uuid;
    }

    private function getUuid(): string
    {
        return $this->uuid;
    }

    public function name($name=null): string
    {
        if($name !== null){
            $this->setName($name);
        }
        return $this->getName();
    }

    private function setName($name=''): void
    {
        $this->name = $name;
    }

    private function getName(): string
    {
        return $this->name;
    }

    public function rank($rank=null): int
    {
        if($rank !== null){
            $this->setRank($rank);
        }
        return $this->getRank();
    }

    private function setRank($rank=1): void
    {
        $this->rank = $rank + 0;
    }

    private function getRank(): int
    {
        return $this->rank;
    }

    public function permissions(){
        $permissions = [
            [
                'name' => 'Event.sync',
                'attributes' => [],
                'role' => 'ROLE_SYSTEM'
            ]
        ];
        return $permissions;
    }

}