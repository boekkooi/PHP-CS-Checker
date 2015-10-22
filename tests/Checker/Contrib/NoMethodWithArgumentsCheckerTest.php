<?php
namespace Tests\Boekkooi\CS\Checker\Contrib;

use Tests\Boekkooi\CS\Checker\AbstractCheckerTestCase;

class NoMethodWithArgumentsCheckerTest extends AbstractCheckerTestCase
{
    public function testCheck()
    {
        $input = '<?php
class test
{
    public function setTest($test) { }
    public function getTest() { \'func_get_args\'; }
    public function methodGetArgs() { func_get_args(); }
    public function methodGetArg() { func_get_arg(1); }
    public function methodNumArgs() { func_num_args(); }
}';

        $messages = [
            '4:28' => [
                [ E_ERROR, 'check_method_arguments_not_allowed', [ 'method' => 'setTest' ] ]
            ],
            '6:38' => [
                [ E_ERROR, 'check_method_arguments_called_not_allowed', [ 'method' => 'methodGetArgs', 'function' => 'func_get_args' ] ]
            ],
            '7:37' => [
                [ E_ERROR, 'check_method_arguments_called_not_allowed', [ 'method' => 'methodGetArg', 'function' => 'func_get_arg' ] ]
            ],
            '8:38' => [
                [ E_ERROR, 'check_method_arguments_called_not_allowed', [ 'method' => 'methodNumArgs', 'function' => 'func_num_args' ] ]
            ]
        ];

        $this->makeTest($messages, $input);
    }
}
