<?php

/**
 * File containing the CoreVoterTest class.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace eZ\Publish\Core\MVC\Symfony\Security\Tests\Voter;

use eZ\Publish\API\Repository\Repository;
use eZ\Publish\API\Repository\Values\ValueObject;
use eZ\Publish\Core\MVC\Symfony\Controller\Content\ViewController;
use eZ\Publish\Core\MVC\Symfony\Security\Authorization\Attribute;
use eZ\Publish\Core\MVC\Symfony\Security\Authorization\Voter\CoreVoter;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use PHPUnit\Framework\TestCase;

class CoreVoterTest extends TestCase
{
    /** @var \eZ\Publish\API\Repository\Repository|\PHPUnit\Framework\MockObject\MockObject */
    private $repository;

    protected function setUp()
    {
        parent::setUp();
        $this->repository = $this->createMock(Repository::class);
    }

    /**
     * @dataProvider supportsAttributeProvider
     */
    public function testSupportsAttribute($attribute, $expectedResult)
    {
        $voter = new CoreVoter($this->repository);
        $this->assertSame($expectedResult, $voter->supportsAttribute($attribute));
    }

    public function supportsAttributeProvider()
    {
        return [
            ['foo', false],
            [new Attribute('foo', 'bar'), true],
            [new Attribute('foo', 'bar', ['some' => 'thing']), false],
            [new \stdClass(), false],
            [['foo'], false],
            [
                new Attribute(
                    'foo',
                    'bar',
                    ['valueObject' => $this->getMockForAbstractClass(ValueObject::class)]
                ),
                false,
            ],
        ];
    }

    /**
     * @dataProvider supportsClassProvider
     */
    public function testSupportsClass($class)
    {
        $voter = new CoreVoter($this->repository);
        $this->assertTrue($voter->supportsClass($class));
    }

    public function supportsClassProvider()
    {
        return [
            ['foo'],
            ['bar'],
            [ValueObject::class],
            [ViewController::class],
        ];
    }

    /**
     * @dataProvider voteInvalidAttributeProvider
     */
    public function testVoteInvalidAttribute(array $attributes)
    {
        $voter = new CoreVoter($this->repository);
        $this->assertSame(
            VoterInterface::ACCESS_ABSTAIN,
            $voter->vote(
                $this->createMock(TokenInterface::class),
                new \stdClass(),
                $attributes
            )
        );
    }

    public function voteInvalidAttributeProvider()
    {
        return [
            [[]],
            [['foo']],
            [['foo', 'bar', ['some' => 'thing']]],
            [[new \stdClass()]],
            [
                [
                    new Attribute(
                        'foo',
                        'bar',
                        ['valueObject' => $this->getMockForAbstractClass(ValueObject::class)]
                    ),
                ],
                false,
            ],
        ];
    }

    /**
     * @dataProvider voteProvider
     */
    public function testVote(Attribute $attribute, $repositoryCanUser, $expectedResult)
    {
        $voter = new CoreVoter($this->repository);
        if ($repositoryCanUser !== null) {
            $this->repository
                ->expects($this->once())
                ->method('hasAccess')
                ->with($attribute->module, $attribute->function)
                ->willReturn($repositoryCanUser);
        } else {
            $this->repository
                ->expects($this->never())
                ->method('hasAccess');
        }

        $this->assertSame(
            $expectedResult,
            $voter->vote(
                $this->createMock(TokenInterface::class),
                new \stdClass(),
                [$attribute]
            )
        );
    }

    public function voteProvider()
    {
        return [
            [
                new Attribute('content', 'read'),
                true,
                VoterInterface::ACCESS_GRANTED,
            ],
            [
                new Attribute('foo', 'bar'),
                true,
                VoterInterface::ACCESS_GRANTED,
            ],
            [
                new Attribute('content', 'read'),
                false,
                VoterInterface::ACCESS_DENIED,
            ],
            [
                new Attribute('some', 'thing'),
                false,
                VoterInterface::ACCESS_DENIED,
            ],
            [
                new Attribute(
                    'content',
                    'read',
                    [
                        'valueObject' => $this->getMockForAbstractClass(ValueObject::class),
                        'targets' => $this->getMockForAbstractClass(ValueObject::class),
                    ]
                ),
                null,
                VoterInterface::ACCESS_ABSTAIN,
            ],
            [
                new Attribute(
                    'content',
                    'read',
                    [
                        'valueObject' => $this->getMockForAbstractClass(ValueObject::class),
                        'targets' => [$this->getMockForAbstractClass(ValueObject::class)],
                    ]
                ),
                null,
                VoterInterface::ACCESS_ABSTAIN,
            ],
            [
                new Attribute(
                    'content',
                    'read',
                    [
                        'valueObject' => $this->getMockForAbstractClass(ValueObject::class),
                        'targets' => $this->getMockForAbstractClass(ValueObject::class),
                    ]
                ),
                null,
                VoterInterface::ACCESS_ABSTAIN,
            ],
            [
                new Attribute(
                    'content',
                    'read',
                    [
                        'valueObject' => $this->getMockForAbstractClass(ValueObject::class),
                        'targets' => [$this->getMockForAbstractClass(ValueObject::class)],
                    ]
                ),
                null,
                VoterInterface::ACCESS_ABSTAIN,
            ],
        ];
    }
}
