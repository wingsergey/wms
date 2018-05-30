<?php

namespace App\Controller\Api;

use App\Entity\Product;
use App\Entity\Unit;
use App\Entity\UnitEqual;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class UnitController
 * @package App\Controller\Api
 *
 * @Route("/api/unit")
 */
class UnitController extends ApiController
{
    /**
     * List user units
     *
     * @ApiDoc(
     *  resource=true,
     *  section="Unit",
     *  headers={
     *      {"name"="User-ID", "required"=true, "description"="User UUID"}
     *  },
     *  statusCodes={
     *      200="Returned when successful",
     *      401="Returned when User ID is not passed"
     *  },
     *  filters={
     *      {"name"="commonOnly", "dataType"="integer", "description"="Return only common units if set value not equal to 0"},
     *      {"name"="productId", "dataType"="UUID", "description"="Product ID to return product assigned units only. This setting has highest priority than commonOnly."}
     *  }
     * )
     *
     * @Route("", name="unit_list")
     * @Method("GET")
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function listAction(Request $request) : JsonResponse
    {
        $em = $this->getDoctrine()->getManager();

        /** @var Product $product */
        $product = $em->getRepository(Product::class)->findForUser($this->commonProcessor->getUserUUID(), $request->request->get('productId'));
        if ($product) {
            $units = $this->getDoctrine()->getRepository(Unit::class)->findByForUser($this->commonProcessor->getUserUUID(), ['product' => $product]);
        } elseif ($request->request->get('commonOnly')) {
            $units = $em->getRepository(Unit::class)->getUserStandardUnits($this->commonProcessor->getUserUUID());
        } else {
            $units = $this->getDoctrine()->getRepository(Unit::class)->findAllForUser($this->commonProcessor->getUserUUID());
        }

        return $this->responseAction([
            'objects' => $units,
        ]);
    }

    /**
     * Get unit by ID
     *
     * @ApiDoc(
     *  resource=true,
     *  section="Unit",
     *  headers={
     *      {"name"="User-ID", "required"=true, "description"="User UUID"}
     *  },
     *  statusCodes={
     *      200="Returned when successful",
     *      401="Returned when User ID is not passed",
     *      404="Returned when no Unit is found"
     *  }
     * )
     *
     * @Route("/{id}", name="unit_get", requirements={"id": "([0-9a-fA-F]){8}-([0-9a-fA-F]){4}-([0-9a-fA-F]){4}-([0-9a-fA-F]){4}-([0-9a-fA-F]){12}"})
     * @Method("GET")
     *
     * @param string $id Unit
     * @return JsonResponse
     */
    public function getAction(string $id) : JsonResponse
    {
        $entity = $this->getDoctrine()->getRepository(Unit::class)->findForUser($this->commonProcessor->getUserUUID(), $id);

        return $this->responseAction([
            'objects' => $entity,
        ]);
    }

    /**
     * Create unit
     *
     * @ApiDoc(
     *  resource=true,
     *  section="Unit",
     *  headers={
     *      {"name"="User-ID", "required"=true, "description"="User UUID"}
     *  },
     *  parameters={
     *      {"name"="productId", "dataType"="UUID", "required"=false, "description"="Unit product ID"},
     *      {"name"="externalId", "dataType"="string", "required"=false, "description"="Unit external ID"},
     *      {"name"="title", "dataType"="string", "required"=true, "description"="Unit title eg. 'milliliters'"},
     *      {"name"="shortForm", "dataType"="string", "required"=false, "description"="Short form eg. 'ml.'"},
     *      {"name"="description", "dataType"="string", "required"=false, "description"="Unit description"}
     *  },
     *  statusCodes={
     *      201="Returned when successful",
     *      401="Returned when User ID is not passed"
     *  }
     * )
     *
     * @Route("/create", name="unit_create")
     * @Method("POST")
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function createAction(Request $request) : JsonResponse
    {
        $em = $this->getDoctrine()->getManager();

        $errors = \App\Validator\Api\Unit::create($request);
        if (count($errors) > 0) {
            return $this->violationFailedResponseAction($errors);
        }

        $entity = new Unit;
        $entity->fillFromRequest($request);
        $entity->setUserId($this->commonProcessor->getUserUUID());

        if ($request->request->has('productId') && $request->request->get('productId')) {
            $product = $em->getRepository(Product::class)->findForUser($this->commonProcessor->getUserUUID(), $request->request->get('productId'));
            $entity->setProduct($product);
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
        ], 201);
    }

    /**
     * Update unit
     *
     * @ApiDoc(
     *  resource=true,
     *  section="Unit",
     *  headers={
     *      {"name"="User-ID", "required"=true, "description"="User UUID"}
     *  },
     *  parameters={
     *      {"name"="productId", "dataType"="UUID", "required"=false, "description"="Unit product ID"},
     *      {"name"="externalId", "dataType"="string", "required"=false, "description"="Unit external ID"},
     *      {"name"="title", "dataType"="string", "required"=false, "description"="Unit title eg. 'milliliters'"},
     *      {"name"="shortForm", "dataType"="string", "required"=false, "description"="Short form eg. 'ml.'"},
     *      {"name"="description", "dataType"="string", "required"=false, "description"="Unit description"}
     *  },
     *  statusCodes={
     *      200="Returned when successful",
     *      401="Returned when User ID is not passed"
     *  }
     * )
     *
     * @Route("/{id}", name="unit_update", requirements={"id": "([0-9a-fA-F]){8}-([0-9a-fA-F]){4}-([0-9a-fA-F]){4}-([0-9a-fA-F]){4}-([0-9a-fA-F]){12}"})
     * @Method("POST")
     *
     * @param string $id Unit
     * @param Request $request
     * @return JsonResponse
     */
    public function updateAction(string $id, Request $request) : JsonResponse
    {
        $em = $this->getDoctrine()->getManager();

        $errors = \App\Validator\Api\Unit::update($request);
        if (count($errors) > 0) {
            return $this->violationFailedResponseAction($errors);
        }

        $entity = $em->getRepository(Unit::class)->findForUser($this->commonProcessor->getUserUUID(), $id);
        if (!$entity) {
            return $this->errorResponseAction('Unit is not found or already deleted');
        }

        $entity->fillFromRequest($request);

        if ($request->request->has('productId') && $request->request->get('productId')) {
            $product = $em->getRepository(Product::class)->findForUser($this->commonProcessor->getUserUUID(), $request->request->get('productId'));
            $entity->setProduct($product);
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
     * Delete unit
     *
     * @ApiDoc(
     *  resource=true,
     *  section="Unit",
     *  headers={
     *      {"name"="User-ID", "required"=true, "description"="User UUID"}
     *  },
     *  parameters={
     *      {"name"="unitId", "dataType"="UUID", "required"=true, "description"="Unit ID"}
     *  },
     *  statusCodes={
     *      200="Returned when successful",
     *      401="Returned when User ID is not passed",
     *      404="Returned when no Unit is found"
     *  }
     * )
     *
     * @Route("/delete", name="unit_delete")
     * @Method("POST")
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function deleteAction(Request $request) : JsonResponse
    {
        $em = $this->getDoctrine()->getManager();

        $errors = \App\Validator\Api\Unit::delete($request);
        if (count($errors) > 0) {
            return $this->violationFailedResponseAction($errors);
        }

        $unit = $em->getRepository(Unit::class)->findForUser($this->commonProcessor->getUserUUID(), $request->request->get('unitId'));

        if (!$unit) {
            return $this->errorResponseAction('Unit is not found or already deleted');
        }

        $em->remove($unit);
        $em->flush();

        return $this->responseAction();
    }

    /**
     * List user unit equals
     *
     * @ApiDoc(
     *  resource=true,
     *  section="Unit",
     *  headers={
     *      {"name"="User-ID", "required"=true, "description"="User UUID"}
     *  },
     *  statusCodes={
     *      200="Returned when successful",
     *      401="Returned when User ID is not passed"
     *  }
     * )
     *
     * @Route("-equal", name="unit_equal_list")
     * @Method("GET")
     *
     * @return JsonResponse
     */
    public function listUnitEqualAction() : JsonResponse
    {
        $units = $this->getDoctrine()->getRepository(UnitEqual::class)->findAllForUser($this->commonProcessor->getUserUUID());

        return $this->responseAction([
            'objects' => $units,
        ]);
    }

    /**
     * Get unit equal by ID
     *
     * @ApiDoc(
     *  resource=true,
     *  section="Unit",
     *  headers={
     *      {"name"="User-ID", "required"=true, "description"="User UUID"}
     *  },
     *  statusCodes={
     *      200="Returned when successful",
     *      401="Returned when User ID is not passed",
     *      404="Returned when no unit equal is found"
     *  }
     * )
     *
     * @Route("-equal/{id}", name="unit_equal_get", requirements={"id": "([0-9a-fA-F]){8}-([0-9a-fA-F]){4}-([0-9a-fA-F]){4}-([0-9a-fA-F]){4}-([0-9a-fA-F]){12}"})
     * @Method("GET")
     *
     * @param string $id UnitEqual ID
     * @return JsonResponse
     */
    public function getUnitEqualAction(string $id) : JsonResponse
    {
        $entity = $this->getDoctrine()->getRepository(UnitEqual::class)->findForUser($this->commonProcessor->getUserUUID(), $id);

        return $this->responseAction([
            'objects' => $entity,
        ]);
    }

    /**
     * Create unit equal
     *
     * @ApiDoc(
     *  resource=true,
     *  section="Unit",
     *  headers={
     *      {"name"="User-ID", "required"=true, "description"="User UUID"}
     *  },
     *  parameters={
     *      {"name"="saleUnitId", "dataType"="UUID", "required"=true, "description"="Unit id for sale"},
     *      {"name"="storageUnitId", "dataType"="UUID", "required"=true, "description"="Unit id for storage"},
     *      {"name"="equal", "dataType"="integer", "required"=true, "description"="Equality qty"}
     *  },
     *  statusCodes={
     *      201="Returned when successful",
     *      401="Returned when User ID is not passed"
     *  }
     * )
     *
     * @Route("-equal/create", name="unit_equal_create")
     * @Method("POST")
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function createUnitEqualityAction(Request $request) : JsonResponse
    {
        $em = $this->getDoctrine()->getManager();

        $errors = \App\Validator\Api\Unit::createUnitEquality($request);

        if (count($errors) > 0) {
            return $this->violationFailedResponseAction($errors);
        }

        $entity = new UnitEqual();
        $entity->fillFromRequest($request);

        $saleUnit = $em->getRepository(Unit::class)->findForUser($this->commonProcessor->getUserUUID(), $request->request->get('saleUnitId'));

        $storageUnit = $em->getRepository(Unit::class)->findForUser($this->commonProcessor->getUserUUID(), $request->request->get('storageUnitId'));

        $entity->setSaleUnit($saleUnit);
        $entity->setStorageUnit($storageUnit);

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
     * Update unit equality
     *
     * @ApiDoc(
     *  resource=true,
     *  section="Unit",
     *  headers={
     *      {"name"="User-ID", "required"=true, "description"="User UUID"}
     *  },
     *  parameters={
     *      {"name"="saleUnitId", "dataType"="UUID", "required"=false, "description"="Unit id for sale"},
     *      {"name"="storageUnitId", "dataType"="UUID", "required"=false, "description"="Unit id for storage"},
     *      {"name"="equal", "dataType"="integer", "required"=false, "description"="Equality qty"}
     *  },
     *  statusCodes={
     *      200="Returned when successful",
     *      401="Returned when User ID is not passed"
     *  }
     * )
     *
     * @Route("-equal/{id}", name="unit_equal_update", requirements={"id": "([0-9a-fA-F]){8}-([0-9a-fA-F]){4}-([0-9a-fA-F]){4}-([0-9a-fA-F]){4}-([0-9a-fA-F]){12}"})
     * @Method("POST")
     *
     * @param string $id UnitEqual ID
     * @param Request $request
     * @return JsonResponse
     */
    public function updateUnitEqualityAction(string $id, Request $request) : JsonResponse
    {
        $em = $this->getDoctrine()->getManager();

        $errors = \App\Validator\Api\Unit::updateUnitEquality($request);
        if (count($errors) > 0) {
            return $this->violationFailedResponseAction($errors);
        }

        /** @var UnitEqual $entity */
        $entity = $em->getRepository(UnitEqual::class)->findForUser($this->commonProcessor->getUserUUID(), $id);
        if (!$entity) {
            return $this->errorResponseAction('Unit equal is not found or already deleted');
        }

        $entity->fillFromRequest($request);

        if ($request->request->has('saleUnitId')) {
            $saleUnit = $em->getRepository(Unit::class)->findForUser($this->commonProcessor->getUserUUID(), $request->request->get('saleUnitId'));
            $entity->setSaleUnit($saleUnit);
        }

        if ($request->request->has('storageUnitId')) {
            $storageUnit = $em->getRepository(Unit::class)->findForUser($this->commonProcessor->getUserUUID(), $request->request->get('storageUnitId'));
            $entity->setStorageUnit($storageUnit);
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
     * Delete unit equality
     *
     * @ApiDoc(
     *  resource=true,
     *  section="Unit",
     *  headers={
     *      {"name"="User-ID", "required"=true, "description"="User UUID"}
     *  },
     *  parameters={
     *      {"name"="unitEqualId", "dataType"="UUID", "required"=true, "description"="Unit Equal ID"}
     *  },
     *  statusCodes={
     *      200="Returned when successful",
     *      401="Returned when User ID is not passed",
     *      404="Returned when no UnitEqual is found"
     *  }
     * )
     *
     * @Route("-equal/delete", name="unit_equal_delete")
     * @Method("POST")
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function deleteEqualityAction(Request $request) : JsonResponse
    {
        $em = $this->getDoctrine()->getManager();

        $errors = \App\Validator\Api\Unit::deleteEquality($request);
        if (count($errors) > 0) {
            return $this->violationFailedResponseAction($errors);
        }

        $unit = $em->getRepository(UnitEqual::class)->findForUser($this->commonProcessor->getUserUUID(), $request->request->get('unitEqualId'));

        if (!$unit) {
            return $this->errorResponseAction('Unit Equal is not found or already deleted');
        }

        $em->remove($unit);
        $em->flush();

        return $this->responseAction();
    }
}
