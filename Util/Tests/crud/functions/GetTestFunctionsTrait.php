<?php

namespace EasyApiBundle\Util\Tests\crud\functions;

use EasyApiBundle\Util\ApiProblem;
use Symfony\Component\HttpFoundation\Response;

trait GetTestFunctionsTrait
{
    use crudFunctionsTestTrait;

    /**
     * GET - Nominal case.
     * @param int|null $id
     * @param string|null $filename
     * @param string|null $userLogin
     * @param string|null $userPassword
     */
    public function doTestGet(int $id = null, string $filename = 'nominalCase.json', string $userLogin = null, string $userPassword = null): void
    {
        $apiOutput = self::httpGetWithLogin(['name' => static::getGetRouteName(), 'params' => ['id' => $id ?? static::defaultEntityId]], $userLogin, $userPassword);

        self::assertEquals(Response::HTTP_OK, $apiOutput->getStatusCode());
        $result = $apiOutput->getData();
        $expectedResult = $this->getExpectedResponse($filename, 'Get', $result);

        static::assertAssessableContent($expectedResult, $result);
        static::assertEquals($expectedResult, $result);
    }

    /**
     * GET - Error case - not found.
     * @param int|null $id
     * @param string|null $userLogin
     * @param string|null $userPassword
     */
    public function doTestGetNotFound(int $id = null, string $userLogin = null, string $userPassword = null): void
    {
        $apiOutput = self::httpGetWithLogin(['name' => static::getGetRouteName(), 'params' => ['id' => $id ?? 99999999]], $userLogin, $userPassword);
        static::assertApiProblemError($apiOutput, Response::HTTP_NOT_FOUND, [sprintf(ApiProblem::ENTITY_NOT_FOUND, 'entity')]);
    }

    /**
     * GET - Error case - 401 - Without authentication.
     * @param int|null $id
     */
    public function doTestGetWithoutAuthentication(int $id = null): void
    {
        $apiOutput = self::httpGet(['name' => static::getGetRouteName(), 'params' => ['id' => $id ?? static::defaultEntityId]], false);
        static::assertApiProblemError($apiOutput, Response::HTTP_UNAUTHORIZED, [ApiProblem::JWT_NOT_FOUND]);
    }

    /**
     * GET - Error case - 403 - Missing right.
     * @param int|null $id
     * @param string|null $userLogin
     * @param string|null $userPassword
     */
    public function doTestGetWithoutRight(int $id = null, string $userLogin = null, string $userPassword = null): void
    {
        if(null === $userLogin && null === $userPassword) {
            $userLogin = static::USER_NORULES_TEST_USERNAME;
            $userPassword = static::USER_NORULES_TEST_PASSWORD;
        }

        $apiOutput = self::httpGetWithLogin(['name' => static::getGetRouteName(), 'params' => ['id' => $id ?? static::defaultEntityId]], $userLogin, $userPassword);

        static::assertApiProblemError($apiOutput, Response::HTTP_FORBIDDEN, [ApiProblem::RESTRICTED_ACCESS]);
    }
}
