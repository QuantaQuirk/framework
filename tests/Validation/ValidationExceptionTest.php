<?php

namespace QuantaQuirk\Tests\Validation;

use QuantaQuirk\Translation\ArrayLoader;
use QuantaQuirk\Translation\Translator;
use QuantaQuirk\Validation\ValidationException;
use QuantaQuirk\Validation\Validator;
use PHPUnit\Framework\TestCase;

class ValidationExceptionTest extends TestCase
{
    public function testExceptionSummarizesZeroErrors()
    {
        $exception = $this->getException([], []);

        $this->assertSame('The given data was invalid.', $exception->getMessage());
    }

    public function testExceptionSummarizesOneError()
    {
        $exception = $this->getException([], ['foo' => 'required']);

        $this->assertSame('validation.required', $exception->getMessage());
    }

    public function testExceptionSummarizesTwoErrors()
    {
        $exception = $this->getException([], ['foo' => 'required', 'bar' => 'required']);

        $this->assertSame('validation.required (and 1 more error)', $exception->getMessage());
    }

    public function testExceptionSummarizesThreeOrMoreErrors()
    {
        $exception = $this->getException([], [
            'foo' => 'required',
            'bar' => 'required',
            'baz' => 'required',
        ]);

        $this->assertSame('validation.required (and 2 more errors)', $exception->getMessage());
    }

    public function testExceptionErrorZeroErrors()
    {
        $exception = $this->getException([], []);

        $this->assertSame([], $exception->errors());
    }

    public function testExceptionErrorOneError()
    {
        $exception = $this->getException([], ['foo' => 'required']);

        $this->assertSame(['foo' => ['validation.required']], $exception->errors());
    }

    public function testExceptionStatusOneError()
    {
        $exception = $this->getException([], ['foo' => 'required']);
        $exception->status(500);

        $this->assertEquals(500, $exception->status);
    }

    public function testExceptionErrorBagOneError()
    {
        $exception = $this->getException([], ['foo' => 'required']);
        $exception->errorBag('milwad');

        $this->assertEquals('milwad', $exception->errorBag);
    }

    public function testExceptionRedirectToOneError()
    {
        $exception = $this->getException([], ['foo' => 'required']);
        $exception->redirectTo('https://google.com');

        $this->assertEquals('https://google.com', $exception->redirectTo);
    }

    public function testExceptionGetResponseOneError()
    {
        $exception = $this->getException([], ['foo' => 'required']);

        $this->assertNull($exception->getResponse());
    }

    protected function getException($data = [], $rules = [])
    {
        $translator = new Translator(new ArrayLoader, 'en');
        $validator = new Validator($translator, $data, $rules);

        return new ValidationException($validator);
    }
}