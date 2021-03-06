<?php

namespace EasyApiBundle\Util\Tests;

use EasyApiBundle\Util\ApiProblem;

trait AssertionsTrait
{
    protected static $assessableFunctions = [
        'assertDateTime' => '\\\assertDateTime\(\)'
    ];

    /**
     * Determine if two arrays are similar.
     *
     * @param array $a
     * @param array $b
     */
    protected static function assertArraysAreSimilar(array $a, array $b): void
    {
        sort($a);
        sort($b);

        static::assertEquals($a, $b);
    }

    /**
     * Determine if two associative arrays are similar.
     *
     * Both arrays must have the same indexes with identical values
     * without respect to key ordering
     *
     * @param array $a
     * @param array $b
     */
    protected static function assertAssociativeArraysAreSimilar(array $a, array $b): void
    {
        // Indexes must match
        static::assertCount(count($a), $b, 'The array have not the same size');

        // Compare values
        foreach ($a as $k => $v) {
            static::assertTrue(isset($b[$k]), "The second array have not the key '{$k}'");
            static::assertEquals($v, $b[$k], "Values for '{$k}' key do not match");
        }
    }

    /**
     * Asserts an API problem standard error.
     *
     * @param int       $expectedStatus
     * @param array     $messages
     * @param ApiOutput $apiOutput
     */
    protected static function assertApiProblemError(ApiOutput $apiOutput, int $expectedStatus, array $messages): void
    {
        static::assertEquals($expectedStatus, $apiOutput->getStatusCode());
        $error = $apiOutput->getData();
        static::assertArrayHasKey('errors', $error);
        array_walk($messages, static function (&$message) { $message = ApiProblem::PREFIX.$message; });
        static::assertArraysAreSimilar($messages, $error['errors']);
    }

    /**
     * Asserts an API entity standard result.
     *
     * @param ApiOutput $apiOutput      API output
     * @param int       $expectedStatus Expected status
     * @param array     $data           Expected data (only field or with values
     * @param bool      $onlyFields     Check only fields (check values instead)
     */
    protected static function assertApiEntityResult(ApiOutput $apiOutput, int $expectedStatus, array $data, $onlyFields = true): void
    {
        static::assertEquals($expectedStatus, $apiOutput->getStatusCode());
        if (true === $onlyFields) {
            static::assertFields($data, $apiOutput->getData());
        } else {
            static::assertAssociativeArraysAreSimilar($data, $apiOutput->getData());
        }
    }

    /**
     * Asserts an API entity standard result.
     *
     * @param ApiOutput $apiOutput      API output
     * @param int       $expectedStatus Expected status
     * @param int       $count          List count
     * @param array     $fields         Expected fields
     */
    protected static function assertApiEntityListResult(ApiOutput $apiOutput, int $expectedStatus, int $count, array $fields): void
    {
        static::assertEquals($expectedStatus, $apiOutput->getStatusCode());
        $data = $apiOutput->getData();
        static::assertCount($count, $data, "Expected list size : {$count}, get ".count($data));
        foreach ($data as $entity) {
            static::assertFields($fields, $entity);
        }
    }

    /**
     * Asserts that entity contains exactly theses fields.
     *
     * @param array $fields Expected fields
     * @param array $entity JSON entity as array
     */
    protected static function assertFields(array $fields, array $entity): void
    {
        static::assertNotNull($entity, 'The entity should not be null !');
        static::assertCount(count($fields), $entity, 'Expected field count : '.count($fields).', get '.count($entity));
        foreach ($fields as $field) {
            static::assertArrayHasKey($field, $entity, "Entity must have this field : {$field}");
        }
    }

    /**
     * Asserts that array $expected is the same as $result using assertions methods in expected result
     *
     * @param array $expected
     * @param array $result
     */
    protected static function assertAssessableContent(array &$expected, array &$result): void
    {
        foreach ($expected as $key => $value) {
            if (array_key_exists($key, $result)) {
                if (!is_array($value)) {
                    foreach (static::$assessableFunctions as $functionName => $functionExpr) {
                        if (preg_match("/{$functionExpr}/", $value)) {
                            static::$functionName($key, $value, $result[$key]);
                            unset($expected[$key]);
                            unset($result[$key]);
                        }
                    }
                } elseif(is_array($result[$key])) {
                    static::assertAssessableContent($expected[$key], $result[$key]);
                }
            }
        }
    }

    /**
     * @param $key
     * @param $expected
     * @param $value
     */
    protected static function assertDateTime($key, $expected, $value): void
    {
        $expectedFormat = 'Y-m-d H:i:s';
        $errorMessage = "invalid date format for {$key} field: expected format {$expectedFormat}, get value {$value}";
        $date = \DateTime::createFromFormat($expectedFormat, $value);
        static::assertTrue($date && ($date->format($expectedFormat) === $value), $errorMessage);
    }
}
