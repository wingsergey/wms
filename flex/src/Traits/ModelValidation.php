<?php

namespace App\Traits;

use App\Entity\AbstractEntity;
use Symfony\Component\HttpFoundation\Request;

/**
 * Trait ModelValidation
 * @package App\Traits
 */
trait ModelValidation
{
    /**
     * @param Request $request
     * @return AbstractEntity
     */
    public function fillFromRequest(Request $request)
    {
        return $this->fillEntityFromRequest($this, $request);
    }

    /**
     * @param $entity
     * @param Request $request
     * @return mixed
     */
    public function fillEntityFromRequest($entity, Request $request)
    {
        $post = $request->request->all();

        return $this->fillFromArray($entity, $post);
    }

    /**
     * @param $entity
     * @param array $input
     * @return mixed
     */
    public function fillFromArray($entity, array $input)
    {
        foreach ($input as $item => $value) {
            $method = 'set' . ucfirst($item);
            if (method_exists($entity, $method)) {
                $entity->$method($value);
            }
        }

        return $entity;
    }

    /**
     * @param Request $request
     * @return \App\Entity\Unit
     */
    public function createEntityFromRequest(Request $request)
    {
        $entity = $this->createEntity();
        $this->fillEntityFromRequest($entity, $request);

        return $entity;
    }

    /**
     * @return mixed
     */
    public function createEntity()
    {
        $entity = new parent();

        return $entity;
    }

    /**
     * @return mixed
     */
    public function createEntityFromModel()
    {
        $entity = $this->createEntity();

        return $this->fillFromArray($entity, $this->getVars());
    }
}
