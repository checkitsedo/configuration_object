<?php
namespace Romm\ConfigurationObject\Tests\Unit;

use Romm\ConfigurationObject\ConfigurationObjectInstance;
use Romm\ConfigurationObject\Exceptions\Exception;
use Romm\ConfigurationObject\Tests\Fixture\Model\DummyConfigurationObject;
use Romm\ConfigurationObject\Tests\Fixture\Model\DummyConfigurationObjectWithAttributeContainingError;
use Romm\ConfigurationObject\Tests\Fixture\Validator\WrongValueValidator;
use TYPO3\CMS\Extbase\Error\Error;
use TYPO3\CMS\Extbase\Error\Result;

class ConfigurationObjectInstanceTest extends AbstractUnitTest
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->injectMockedValidatorResolverInCore();
    }

    /**
     * Will check a full workflow of the life of an instance of the class
     * `ConfigurationObjectInstance`.
     *
     * @test
     */
    public function checkEverythingWorksCorrectly()
    {
        $dummyConfigurationObject = new DummyConfigurationObjectWithAttributeContainingError();
        $dummyConfigurationObject->setFoo(WrongValueValidator::WRONG_VALUE);
        $mapperResult = new Result();

        $configurationObjectInstance = new ConfigurationObjectInstance($dummyConfigurationObject, $mapperResult);

        $this->assertFalse($configurationObjectInstance->hasValidationResult());

        $result = new Result();
        $error = new Error('hello world!', 1337);
        $result->addError($error);

        $configurationObjectInstance->setValidationResult($result);

        $this->assertTrue($configurationObjectInstance->hasValidationResult());
        $this->assertEquals(
            serialize($result),
            serialize($configurationObjectInstance->getValidationResult())
        );

        $this->assertEquals(
            serialize($dummyConfigurationObject),
            serialize($configurationObjectInstance->getObject(true))
        );

        $configurationObjectInstance->refreshValidationResult();

        $this->assertEquals(
            WrongValueValidator::ERROR_MESSAGE,
            $configurationObjectInstance->getValidationResult()->forProperty('foo')->getFirstError()->getMessage()
        );
    }

    /**
     * Trying to access to the real object of an instance of
     * `ConfigurationObjectInstance` must throw an exception.
     *
     * @test
     */
    public function getObjectWithErrorsThrowsException()
    {
        $dummyConfigurationObject = new DummyConfigurationObjectWithAttributeContainingError();
        $dummyConfigurationObject->setFoo(WrongValueValidator::WRONG_VALUE);
        $mapperResult = new Result();

        $configurationObjectInstance = new ConfigurationObjectInstance($dummyConfigurationObject, $mapperResult);

        $result = new Result();
        $error = new Error('hello world!', 1337);
        $result->addError($error);

        $configurationObjectInstance->setValidationResult($result);

        $this->expectException(Exception::class);
        $configurationObjectInstance->getObject();
    }

    /**
     * @test
     */
    public function getNotExistingValidationResultsLaunchesRefresh()
    {
        $dummyConfigurationObject = new DummyConfigurationObjectWithAttributeContainingError();
        $dummyConfigurationObject->setFoo(WrongValueValidator::WRONG_VALUE);
        $mapperResult = new Result();

        $configurationObjectInstance = new ConfigurationObjectInstance($dummyConfigurationObject, $mapperResult);

        $this->assertFalse($configurationObjectInstance->hasValidationResult());

        $configurationObjectInstance->getValidationResult();

        $this->assertTrue($configurationObjectInstance->hasValidationResult());
    }

    /**
     * When a mapping result with errors is given as constructor argument of a
     * configuration object instance, it should always be returned as validation
     * result.
     *
     * @test
     */
    public function mappingResultWithErrorsIsAlwaysReturned()
    {
        $dummyConfigurationObject = new DummyConfigurationObject;
        $mapperResult = new Result();
        $mapperResult->addError(new Error('foo', 1337));

        $configurationObjectInstance = new ConfigurationObjectInstance($dummyConfigurationObject, $mapperResult);

        $this->assertSame($mapperResult, $configurationObjectInstance->getValidationResult());
        $configurationObjectInstance->refreshValidationResult();
        $this->assertSame($mapperResult, $configurationObjectInstance->getValidationResult());
        $configurationObjectInstance->setValidationResult(new Result);
        $this->assertSame($mapperResult, $configurationObjectInstance->getValidationResult());
    }
}
