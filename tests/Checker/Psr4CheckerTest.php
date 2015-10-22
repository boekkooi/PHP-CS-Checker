<?php
namespace Tests\Boekkooi\CS\Checker;

class Psr4CheckerTest extends AbstractCheckerTestCase
{
    /**
     * @dataProvider provideCases
     */
    public function testCheck($input, $messages, $filePath = __FILE__)
    {
        $this->makeTest($messages, $input, $this->getTestFile($filePath));
    }

    public function provideCases()
    {
        return [
            [
                '<?php',
                [
                    '1:1' => [
                        [ E_ERROR, 'check_psr4_no_namespace_declaration', [ ] ],
                        [ E_ERROR, 'check_psr4_must_have_a_class', [ ] ],
                    ],
                ]
            ],
            [
                '<?php
class test { }
',
                [
                    '1:1' => [
                        [ E_ERROR, 'check_psr4_no_namespace_declaration', [ ] ],
                    ],
                    '2:7' => [
                        [ E_ERROR, 'check_psr4_filename_must_match_classname', [ 'class' => 'test', 'expected' => basename(__FILE__, '.php') ] ]
                    ]
                ]
            ],
            [
                '<?php
namespace Test;

class testing
{ }
',
                [],
                __DIR__ . '/testing.php'
            ]
        ];
    }
}
