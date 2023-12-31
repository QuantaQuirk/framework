<?php

namespace QuantaQuirk\Tests\Integration\Foundation;

use QuantaQuirk\Auth\Access\AuthorizationException;
use QuantaQuirk\Auth\Access\Response;
use QuantaQuirk\Support\Facades\Route;
use Orchestra\Testbench\TestCase;
use Symfony\Component\Process\PhpProcess;

class ExceptionHandlerTest extends TestCase
{
    /**
     * Resolve application HTTP exception handler.
     *
     * @param  \QuantaQuirk\Foundation\Application  $app
     * @return void
     */
    protected function resolveApplicationExceptionHandler($app)
    {
        $app->singleton('QuantaQuirk\Contracts\Debug\ExceptionHandler', 'QuantaQuirk\Foundation\Exceptions\Handler');
    }

    public function testItRendersAuthorizationExceptions()
    {
        Route::get('test-route', fn () => Response::deny('expected message', 321)->authorize());

        // HTTP request...
        $this->get('test-route')
            ->assertStatus(403)
            ->assertSeeText('expected message');

        // JSON request...
        $this->getJson('test-route')
            ->assertStatus(403)
            ->assertExactJson([
                'message' => 'expected message',
            ]);
    }

    public function testItRendersAuthorizationExceptionsWithCustomStatusCode()
    {
        Route::get('test-route', fn () => Response::deny('expected message', 321)->withStatus(404)->authorize());

        // HTTP request...
        $this->get('test-route')
            ->assertStatus(404)
            ->assertSeeText('Not Found');

        // JSON request...
        $this->getJson('test-route')
            ->assertStatus(404)
            ->assertExactJson([
                'message' => 'expected message',
            ]);
    }

    public function testItRendersAuthorizationExceptionsWithStatusCodeTextWhenNoMessageIsSet()
    {
        Route::get('test-route', fn () => Response::denyWithStatus(404)->authorize());

        // HTTP request...
        $this->get('test-route')
            ->assertStatus(404)
            ->assertSeeText('Not Found');

        // JSON request...
        $this->getJson('test-route')
            ->assertStatus(404)
            ->assertExactJson([
                'message' => 'Not Found',
            ]);

        Route::get('test-route', fn () => Response::denyWithStatus(418)->authorize());

        // HTTP request...
        $this->get('test-route')
            ->assertStatus(418)
            ->assertSeeText("I'm a teapot", false);

        // JSON request...
        $this->getJson('test-route')
            ->assertStatus(418)
            ->assertExactJson([
                'message' => "I'm a teapot",
            ]);
    }

    public function testItRendersAuthorizationExceptionsWithStatusButWithoutResponse()
    {
        Route::get('test-route', fn () => throw (new AuthorizationException())->withStatus(418));

        // HTTP request...
        $this->get('test-route')
            ->assertStatus(418)
            ->assertSeeText("I'm a teapot", false);

        // JSON request...
        $this->getJson('test-route')
            ->assertStatus(418)
            ->assertExactJson([
                'message' => "I'm a teapot",
            ]);
    }

    public function testItHasFallbackErrorMessageForUnknownStatusCodes()
    {
        Route::get('test-route', fn () => throw (new AuthorizationException())->withStatus(399));

        // HTTP request...
        $this->get('test-route')
            ->assertStatus(399)
            ->assertSeeText('Whoops, looks like something went wrong.');

        // JSON request...
        $this->getJson('test-route')
            ->assertStatus(399)
            ->assertExactJson([
                'message' => 'Whoops, looks like something went wrong.',
            ]);
    }

    /**
     * @dataProvider exitCodesProvider
     */
    public function testItReturnsNonZeroExitCodesForUncaughtExceptions($providers, $successful)
    {
        $basePath = static::applicationBasePath();
        $providers = json_encode($providers, true);

        $process = new PhpProcess(<<<EOF
<?php

require 'vendor/autoload.php';

\$quantaquirk = Orchestra\Testbench\Foundation\Application::create(basePath: '$basePath', options: ['extra' => ['providers' => $providers]]);
\$quantaquirk->singleton('QuantaQuirk\Contracts\Debug\ExceptionHandler', 'QuantaQuirk\Foundation\Exceptions\Handler');

\$kernel = \$quantaquirk[QuantaQuirk\Contracts\Console\Kernel::class];

return \$kernel->call('throw-exception-command');
EOF, __DIR__.'/../../../', ['APP_RUNNING_IN_CONSOLE' => true]);

        $process->run();

        $this->assertSame($successful, $process->isSuccessful());
    }

    public static function exitCodesProvider()
    {
        yield 'Throw exception' => [[Fixtures\Providers\ThrowUncaughtExceptionServiceProvider::class], false];
        yield 'Do not throw exception' => [[Fixtures\Providers\ThrowExceptionServiceProvider::class], true];
    }
}
