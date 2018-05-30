<?php

namespace App\Controller\Api;

use App\Entity\Warehouse;
use App\Entity\WarehouseZone;
use App\Repository\AbstractRepository;
use App\Service\StockProcessor;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class WarehouseZoneController
 * @package App\Controller\Api
 *
 * @Route("/api/warehouse-zone")
 */
class WarehouseZoneController extends ApiController
{
    /**
     * List user warehouse zones
     *
     * @ApiDoc(
     *  resource=true,
     *  section="Warehouse Zone",
     *  headers={
     *      {"name"="User-ID", "required"=true, "description"="User UUID"}
     *  },
     *  statusCodes={
     *      200="Returned when successful",
     *      401="Returned when User ID is not passed"
     *  },
     *  filters={
     *      {"name"="limit", "dataType"="integer", "description"="Limit results per page for paginated queries, default=100"}
     *  }
     * )
     *
     * @Route("", defaults={"page": "0"}, name="warehouse_zones_list")
     * @Route("/page/{page}", requirements={"page": "[1-9]\d*"}, name="warehouse_zones_list_paginated")
     * @Method("GET")
     *
     * @param int $page Page number
     * @param Request $request
     * @return JsonResponse
     */
    public function listAction(int $page = 0, Request $request) : JsonResponse
    {
        if (!$page) {
            $warehouseZones = $this->getDoctrine()->getRepository(WarehouseZone::class)->findAllForUser($this->commonProcessor->getUserUUID());
        } else {
            $limit = $request->query->get('limit', AbstractRepository::DEFAULT_PAGINATOR_LIMIT);
            $warehouseZones = $this->getDoctrine()->getRepository(WarehouseZone::class)->findPaginatedForUser($this->commonProcessor->getUserUUID(), [], [], $page, $limit);
        }

        return $this->responseAction([
            'objects' => $warehouseZones,
        ]);
    }

    /**
     * Get warehouse zone by ID
     *
     * @ApiDoc(
     *  resource=true,
     *  section="Warehouse Zone",
     *  headers={
     *      {"name"="User-ID", "required"=true, "description"="User UUID"}
     *  },
     *  statusCodes={
     *      200="Returned when successful",
     *      401="Returned when User ID is not passed",
     *      404="Returned when no warehouse zone is found"
     *  }
     * )
     *
     * @Route("/{id}", name="warehouse_zone_get", requirements={"id": "([0-9a-fA-F]){8}-([0-9a-fA-F]){4}-([0-9a-fA-F]){4}-([0-9a-fA-F]){4}-([0-9a-fA-F]){12}"})
     * @Method("GET")
     *
     * @param string $id WarehouseZone ID
     * @return JsonResponse
     */
    public function getAction(string $id) : JsonResponse
    {
        $entity = $this->getDoctrine()->getRepository(WarehouseZone::class)->findForUser($this->commonProcessor->getUserUUID(), $id);

        return $this->responseAction([
            'objects' => $entity,
        ]);
    }

    /**
     * Get warehouse zone by external ID
     *
     * @ApiDoc(
     *  resource=true,
     *  section="Warehouse Zone",
     *  headers={
     *      {"name"="User-ID", "required"=true, "description"="User UUID"}
     *  },
     *  statusCodes={
     *      200="Returned when successful",
     *      401="Returned when User ID is not passed",
     *      404="Returned when no warehouse zone is found"
     *  }
     * )
     *
     * @Route("/{id}/external", name="warehouse_zone_get_external")
     * @Method("GET")
     *
     * @param string $id WarehouseZone external ID
     * @return JsonResponse
     */
    public function getExternalAction(string $id) : JsonResponse
    {
        $entity = $this->getDoctrine()->getRepository(WarehouseZone::class)->findByExternalId($this->commonProcessor->getUserUUID(), $id);

        return $this->responseAction([
            'objects' => $entity,
        ]);
    }

    /**
     * Create warehouse zone
     *
     * @ApiDoc(
     *  resource=true,
     *  section="Warehouse Zone",
     *  headers={
     *      {"name"="User-ID", "required"=true, "description"="User UUID"}
     *  },
     *  parameters={
     *      {"name"="title", "dataType"="string", "required"=true, "description"="Warehouse zone name"},
     *      {"name"="description", "dataType"="string", "required"=false, "description"="Warehouse zone description"},
     *      {"name"="warehouseId", "dataType"="UUID", "required"=false, "description"="Warehouse ID"},
     *      {"name"="externalId", "dataType"="string", "required"=false, "description"="Warehouse Zone external ID"},
     *      {"name"="parentWarehouseZoneId", "dataType"="UUID", "required"=false, "description"="Parent Warehouse Zone ID"},
     *      {"name"="width", "dataType"="float", "required"=false, "description"="Zone width"},
     *      {"name"="height", "dataType"="float", "required"=false, "description"="Zone height"},
     *      {"name"="depth", "dataType"="float", "required"=false, "description"="Zone depth"}
     *  },
     *  statusCodes={
     *      201="Returned when successful",
     *      401="Returned when User ID is not passed"
     *  }
     * )
     *
     * @Route("/create", name="warehouse_zone_create")
     * @Method("POST")
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function createAction(Request $request) : JsonResponse
    {
        $em = $this->getDoctrine()->getManager();

        $errors = \App\Validator\Api\WarehouseZone::create($request);
        if (count($errors) > 0) {
            return $this->violationFailedResponseAction($errors);
        }

        $entity = new WarehouseZone();
        $entity->fillFromRequest($request);
        $entity->setUserId($this->commonProcessor->getUserUUID());

        $warehouse = $em->getRepository(Warehouse::class)->findForUser($this->commonProcessor->getUserUUID(), $request->request->get('warehouseId'));
        $entity->setWarehouse($warehouse);

        $warehouseZone = $em->getRepository(WarehouseZone::class)->findForUser($this->commonProcessor->getUserUUID(), $request->request->get('parentWarehouseZoneId'));
        $entity->setParent($warehouseZone);

        $validator = $this->get('validator');
        $errors    = $validator->validate($entity);
        if (count($errors) > 0) {
            return $this->violationFailedResponseAction($errors);
        }

        $em->persist($entity);
        $em->flush();

        return $this->responseAction([
            'objects' => $entity,
        ], 201);
    }

    /**
     * Update warehouse zone
     *
     * @ApiDoc(
     *  resource=true,
     *  section="Warehouse Zone",
     *  headers={
     *      {"name"="User-ID", "required"=true, "description"="User UUID"}
     *  },
     *  parameters={
     *      {"name"="title", "dataType"="string", "required"=false, "description"="Warehouse zone name"},
     *      {"name"="description", "dataType"="string", "required"=false, "description"="Warehouse zone description"},
     *      {"name"="code", "dataType"="string", "required"=false, "description"="Warehouse zone code"},
     *      {"name"="warehouseId", "dataType"="UUID", "required"=false, "description"="Warehouse ID"},
     *      {"name"="externalId", "dataType"="string", "required"=false, "description"="Warehouse Zone external ID"},
     *      {"name"="parentWarehouseZoneId", "dataType"="UUID", "required"=false, "description"="Parent Warehouse Zone ID"},
     *      {"name"="width", "dataType"="float", "required"=false, "description"="Zone width"},
     *      {"name"="height", "dataType"="float", "required"=false, "description"="Zone height"},
     *      {"name"="depth", "dataType"="float", "required"=false, "description"="Zone depth"}
     *  },
     *  statusCodes={
     *      200="Returned when successful",
     *      401="Returned when User ID is not passed"
     *  }
     * )
     *
     * @Route("/{id}", name="warehouse_zone_update", requirements={"id": "([0-9a-fA-F]){8}-([0-9a-fA-F]){4}-([0-9a-fA-F]){4}-([0-9a-fA-F]){4}-([0-9a-fA-F]){12}"})
     * @Method("POST")
     *
     * @param string $id WarehouseZone ID
     * @param Request $request
     * @return JsonResponse
     */
    public function updateAction(string $id, Request $request) : JsonResponse
    {
        $em = $this->getDoctrine()->getManager();

        $errors = \App\Validator\Api\WarehouseZone::update($request);
        if (count($errors) > 0) {
            return $this->violationFailedResponseAction($errors);
        }

        $entity = $em->getRepository(WarehouseZone::class)->findForUser($this->commonProcessor->getUserUUID(), $id);
        if (!$entity) {
            return $this->errorResponseAction('Warehouse zone is not found or already deleted');
        }

        $entity->fillFromRequest($request);

        if ($request->request->has('warehouseId')) {
            $warehouse = $em->getRepository(Warehouse::class)->findForUser($this->commonProcessor->getUserUUID(), $request->request->get('warehouseId'));
            $entity->setWarehouse($warehouse);
        }

        if ($request->request->has('parentWarehouseZoneId')) {
            $warehouseZone = $em->getRepository(WarehouseZone::class)->findForUser($this->commonProcessor->getUserUUID(), $request->request->get('parentWarehouseZoneId'));
            $entity->setParent($warehouseZone);
        }

        $validator = $this->get('validator');
        $errors    = $validator->validate($entity);

        if (count($errors) > 0) {
            return $this->violationFailedResponseAction($errors);
        }

        $em->persist($entity);
        $em->flush();

        return $this->responseAction([
            'objects' => $entity,
        ]);
    }

    /**
     * Delete warehouse zone
     *
     * @ApiDoc(
     *  resource=true,
     *  section="Warehouse Zone",
     *  headers={
     *      {"name"="User-ID", "required"=true, "description"="User UUID"}
     *  },
     *  parameters={
     *      {"name"="warehouseZoneId", "dataType"="UUID", "required"=true, "description"="Warehouse zone ID"}
     *  },
     *  statusCodes={
     *      200="Returned when successful",
     *      401="Returned when User ID is not passed",
     *      404="Returned when no Warehouse Zone is found"
     *  }
     * )
     *
     * @Route("/delete", name="warehouse_zone_delete")
     * @Method("POST")
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function deleteAction(Request $request) : JsonResponse
    {
        $em = $this->getDoctrine()->getManager();

        $errors = \App\Validator\Api\WarehouseZone::delete($request);
        if (count($errors) > 0) {
            return $this->violationFailedResponseAction($errors);
        }

        $warehouseZone = $em->getRepository(WarehouseZone::class)->findForUser($this->commonProcessor->getUserUUID(), $request->request->get('warehouseZoneId'));

        if (!$warehouseZone) {
            return $this->errorResponseAction('Warehouse zone is not found or already deleted');
        }

        $em->remove($warehouseZone);
        $em->flush();

        return $this->responseAction();
    }

    /**
     * Get warehouse zone stock map
     *
     * @ApiDoc(
     *  resource=true,
     *  section="Warehouse Zone",
     *  headers={
     *      {"name"="User-ID", "required"=true, "description"="User UUID"}
     *  },
     *  statusCodes={
     *      200="Returned when successful",
     *      401="Returned when User ID is not passed",
     *      404="Returned when no Warehouse is found"
     *  }
     * )
     *
     * @Route("/{id}/stock-map", name="warehouse_zone_stock_map", requirements={"id": "([0-9a-fA-F]){8}-([0-9a-fA-F]){4}-([0-9a-fA-F]){4}-([0-9a-fA-F]){4}-([0-9a-fA-F]){12}"})
     * @Method("GET")
     *
     * @param string $id WarehouseZone ID
     * @param StockProcessor $stockProcessor
     * @return JsonResponse
     */
    public function getStockMapAction(string $id, StockProcessor $stockProcessor) : JsonResponse
    {
        /** @var WarehouseZone $entity */
        $entity = $this->getDoctrine()->getRepository(WarehouseZone::class)->findForUser($this->commonProcessor->getUserUUID(), $id);

        if (!$entity) {
            return $this->errorResponseAction('Warehouse zone is not found or already deleted');
        }

        return $this->responseAction([
            'data' => $stockProcessor->getWarehouseZoneStockMap($entity),
        ]);
    }

    /**
     * Get warehouse zone revisions map
     *
     * @ApiDoc(
     *  resource=true,
     *  section="Warehouse Zone",
     *  headers={
     *      {"name"="User-ID", "required"=true, "description"="User UUID"}
     *  },
     *  statusCodes={
     *      200="Returned when successful",
     *      401="Returned when User ID is not passed",
     *      404="Returned when no Warehouse is found"
     *  }
     * )
     *
     * @Route("/{id}/revision-map", name="warehouse_zone_revision_map", requirements={"id": "([0-9a-fA-F]){8}-([0-9a-fA-F]){4}-([0-9a-fA-F]){4}-([0-9a-fA-F]){4}-([0-9a-fA-F]){12}"})
     * @Method("GET")
     *
     * @param string $id WarehouseZone ID
     * @return JsonResponse
     */
    public function getRevisionMapAction(string $id) : JsonResponse
    {
        /** @var WarehouseZone $entity */
        $entity = $this->getDoctrine()->getRepository(WarehouseZone::class)->findForUser($this->commonProcessor->getUserUUID(), $id);

        if (!$entity) {
            return $this->errorResponseAction('Warehouse zone is not found or already deleted');
        }

        return $this->responseAction([
            'objects' => $entity->getStockRevisions(),
        ]);
    }
}
