<?php
namespace Tests\Boekkooi\CS\Checker\Contrib;

use Tests\Boekkooi\CS\Checker\AbstractCheckerTestCase;

class FinalClassCheckerTest extends AbstractCheckerTestCase
{
    public function testCheck()
    {
        $input = '<?php
class test { }

interface test { } class test2 { }
';

        $messages = [
            '2:1' => [
                [ E_ERROR, 'check_class_should_be_final', [ 'class' => 'test' ] ]
            ],
            '4:20' => [
                [ E_ERROR, 'check_class_should_be_final', [ 'class' => 'test2' ] ]
            ],
        ];

        $this->makeTest($messages, $input);
    }
}
