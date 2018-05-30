<?php

namespace App\Controller\Api;

use App\Entity\Warehouse;
use App\Service\StockProcessor;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class WarehouseController
 * @package App\Controller\Api
 *
 * @Route("/api/warehouse")
 */
class WarehouseController extends ApiController
{
    /**
     * List user warehouses
     *
     * @ApiDoc(
     *  resource=true,
     *  section="Warehouse",
     *  headers={
     *      {"name"="User-ID", "required"=true, "description"="User UUID"}
     *  },
     *  statusCodes={
     *      200="Returned when successful",
     *      401="Returned when User ID is not passed"
     *  }
     * )
     *
     * @Route("", name="warehouses_list")
     * @Method("GET")
     *
     * @return JsonResponse
     */
    public function listAction() : JsonResponse
    {
        $warehouses = $this->getDoctrine()->getRepository(Warehouse::class)->findAllForUser($this->commonProcessor->getUserUUID());

        return $this->responseAction([
            'objects' => $warehouses,
        ]);
    }

    /**
     * Get warehouse by ID
     *
     * @ApiDoc(
     *  resource=true,
     *  section="Warehouse",
     *  headers={
     *      {"name"="User-ID", "required"=true, "description"="User UUID"}
     *  },
     *  statusCodes={
     *      200="Returned when successful",
     *      401="Returned when User ID is not passed",
     *      404="Returned when no warehouse is found"
     *  }
     * )
     *
     * @Route("/{id}", name="warehouse_get", requirements={"id": "([0-9a-fA-F]){8}-([0-9a-fA-F]){4}-([0-9a-fA-F]){4}-([0-9a-fA-F]){4}-([0-9a-fA-F]){12}"})
     * @Method("GET")
     *
     * @param string $id Warehouse ID
     * @return JsonResponse
     */
    public function getAction(string $id) : JsonResponse
    {
        $entity = $this->getDoctrine()->getRepository(Warehouse::class)->findForUser($this->commonProcessor->getUserUUID(), $id);

        return $this->responseAction([
            'objects' => $entity,
        ]);
    }

    /**
     * Get warehouse by external ID
     *
     * @ApiDoc(
     *  resource=true,
     *  section="Warehouse",
     *  headers={
     *      {"name"="User-ID", "required"=true, "description"="User UUID"}
     *  },
     *  statusCodes={
     *      200="Returned when successful",
     *      401="Returned when User ID is not passed",
     *      404="Returned when no warehouse is found"
     *  }
     * )
     *
     * @Route("/{id}/external", name="warehouse_get_external")
     * @Method("GET")
     *
     * @param string $id Warehouse external ID
     * @return JsonResponse
     */
    public function getExternalAction(string $id) : JsonResponse
    {
        $entity = $this->getDoctrine()->getRepository(Warehouse::class)->findByExternalId($this->commonProcessor->getUserUUID(), $id);

        return $this->responseAction([
            'objects' => $entity,
        ]);
    }

    /**
     * Create warehouse
     *
     * @ApiDoc(
     *  resource=true,
     *  section="Warehouse",
     *  headers={
     *      {"name"="User-ID", "required"=true, "description"="User UUID"}
     *  },
     *  parameters={
     *      {"name"="title", "dataType"="string", "required"=true, "description"="Warehouse name"},
     *      {"name"="description", "dataType"="string", "required"=false, "description"="Warehouse description"},
     *      {"name"="externalId", "dataType"="string", "required"=false, "description"="Warehouse external ID"},
     *      {"name"="code", "dataType"="string", "required"=false, "description"="Warehouse code"}
     *  },
     *  statusCodes={
     *      201="Returned when successful",
     *      401="Returned when User ID is not passed"
     *  }
     * )
     *
     * @Route("/create", name="warehouse_create")
     * @Method("POST")
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function createAction(Request $request) : JsonResponse
    {
        $em = $this->getDoctrine()->getManager();

        $errors = \App\Validator\Api\Warehouse::create($request);
        if (count($errors) > 0) {
            return $this->violationFailedResponseAction($errors);
        }

        $entity = new Warehouse();
        $entity->fillFromRequest($request);
        $entity->setUserId($this->commonProcessor->getUserUUID());

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
     * Update warehouse
     *
     * @ApiDoc(
     *  resource=true,
     *  section="Warehouse",
     *  headers={
     *      {"name"="User-ID", "required"=true, "description"="User UUID"}
     *  },
     *  parameters={
     *      {"name"="title", "dataType"="string", "required"=false, "description"="Warehouse name"},
     *      {"name"="description", "dataType"="string", "required"=false, "description"="Warehouse description"},
     *      {"name"="externalId", "dataType"="string", "required"=false, "description"="Warehouse external ID"},
     *      {"name"="code", "dataType"="string", "required"=false, "description"="Warehouse code"}
     *  },
     *  statusCodes={
     *      200="Returned when successful",
     *      401="Returned when User ID is not passed"
     *  }
     * )
     *
     * @Route("/{id}", name="warehouse_update", requirements={"id": "([0-9a-fA-F]){8}-([0-9a-fA-F]){4}-([0-9a-fA-F]){4}-([0-9a-fA-F]){4}-([0-9a-fA-F]){12}"})
     * @Method("POST")
     *
     * @param string $id Warehouse ID
     * @param Request $request
     * @return JsonResponse
     */
    public function updateAction(string $id, Request $request) : JsonResponse
    {
        $em = $this->getDoctrine()->getManager();

        $errors = \App\Validator\Api\Warehouse::update($request);
        if (count($errors) > 0) {
            return $this->violationFailedResponseAction($errors);
        }

        $entity = $em->getRepository(Warehouse::class)->findForUser($this->commonProcessor->getUserUUID(), $id);
        if (!$entity) {
            return $this->errorResponseAction('Warehouse is not found or already deleted');
        }

        $entity->fillFromRequest($request);

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
     * Delete warehouse
     *
     * @ApiDoc(
     *  resource=true,
     *  section="Warehouse",
     *  headers={
     *      {"name"="User-ID", "required"=true, "description"="User UUID"}
     *  },
     *  parameters={
     *      {"name"="warehouseId", "dataType"="UUID", "required"=true, "description"="Warehouse ID"}
     *  },
     *  statusCodes={
     *      200="Returned when successful",
     *      401="Returned when User ID is not passed",
     *      404="Returned when no Warehouse is found"
     *  }
     * )
     *
     * @Route("/delete", name="warehouse_delete")
     * @Method("POST")
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function deleteAction(Request $request) : JsonResponse
    {
        $em = $this->getDoctrine()->getManager();

        $errors = \App\Validator\Api\Warehouse::delete($request);
        if (count($errors) > 0) {
            return $this->violationFailedResponseAction($errors);
        }

        $warehouse = $em->getRepository(Warehouse::class)->findForUser($this->commonProcessor->getUserUUID(), $request->request->get('warehouseId'));

        if (!$warehouse) {
            return $this->errorResponseAction('Warehouse is not found or already deleted');
        }

        $em->remove($warehouse);
        $em->flush();

        return $this->responseAction();
    }

    /**
     * Get warehouse stock map
     *
     * @ApiDoc(
     *  resource=true,
     *  section="Warehouse",
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
     * @Route("/{id}/stock-map", name="warehouse_stock_map", requirements={"id": "([0-9a-fA-F]){8}-([0-9a-fA-F]){4}-([0-9a-fA-F]){4}-([0-9a-fA-F]){4}-([0-9a-fA-F]){12}"})
     * @Method("GET")
     *
     * @param string $id Warehouse ID
     * @param StockProcessor $stockProcessor
     * @return JsonResponse
     */
    public function getStockMapAction(string $id, StockProcessor $stockProcessor) : JsonResponse
    {
        /** @var Warehouse $entity */
        $entity = $this->getDoctrine()->getRepository(Warehouse::class)->findForUser($this->commonProcessor->getUserUUID(), $id);

        if (!$entity) {
            return $this->errorResponseAction('Warehouse is not found or already deleted');
        }

        return $this->responseAction([
            'data' => $stockProcessor->getWarehouseStockMap($entity),
        ]);
    }
}
