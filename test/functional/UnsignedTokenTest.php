<?php
/**
 * This file is part of Lcobucci\JWT, a simple library to handle JWT and JWS
 *
 * @license http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 */

declare(strict_types=1);

namespace Lcobucci\JWT\FunctionalTests;

use DateTimeImmutable;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Token;
use Lcobucci\JWT\Validation\Constraint;
use Lcobucci\JWT\Validation\Constraint\IdentifiedBy;
use Lcobucci\JWT\Validation\Constraint\IssuedBy;
use Lcobucci\JWT\Validation\Constraint\PermittedFor;
use Lcobucci\JWT\Validation\Constraint\ValidAt;
use Lcobucci\JWT\Validation\ConstraintViolationException;

/**
 * @author Luís Otávio Cobucci Oblonczyk <lcobucci@gmail.com>
 * @since 2.1.0
 */
class UnsignedTokenTest extends \PHPUnit\Framework\TestCase
{
    const CURRENT_TIME = 100000;

    /**
     * @var Configuration
     */
    private $config;

    /**
     * @before
     */
    public function createConfiguration(): void
    {
        $this->config = Configuration::forUnsecuredSigner();
    }

    /**
     * @test
     *
     * @covers \Lcobucci\JWT\Configuration
     * @covers \Lcobucci\JWT\Token\Builder
     * @covers \Lcobucci\JWT\Token\Plain
     * @covers \Lcobucci\JWT\Token\DataSet
     * @covers \Lcobucci\JWT\Token\Signature
     * @covers \Lcobucci\JWT\Signer\None
     * @covers \Lcobucci\JWT\Signer\Key
     */
    public function builderCanGenerateAToken(): Token
    {
        $user = ['name' => 'testing', 'email' => 'testing@abc.com'];
        $builder = $this->config->createBuilder();

        $expiration = new DateTimeImmutable('@' . (self::CURRENT_TIME + 3000));

        $token = $builder->identifiedBy('1')
                         ->permittedFor('http://client.abc.com')
                         ->issuedBy('http://api.abc.com')
                         ->expiresAt($expiration)
                         ->withClaim('user', $user)
                         ->getToken($this->config->getSigner(), $this->config->getSigningKey());

        self::assertAttributeEquals(new Token\Signature('', ''), 'signature', $token);
        self::assertEquals(['http://client.abc.com'], $token->claims()->get(Token\RegisteredClaims::AUDIENCE));
        self::assertEquals('http://api.abc.com', $token->claims()->get(Token\RegisteredClaims::ISSUER));
        self::assertSame($expiration, $token->claims()->get(Token\RegisteredClaims::EXPIRATION_TIME));
        self::assertEquals($user, $token->claims()->get('user'));

        return $token;
    }

    /**
     * @test
     *
     * @depends builderCanGenerateAToken
     *
     * @covers \Lcobucci\JWT\Configuration
     * @covers \Lcobucci\JWT\Token\Builder
     * @covers \Lcobucci\JWT\Token\Parser
     * @covers \Lcobucci\JWT\Token\Plain
     * @covers \Lcobucci\JWT\Token\DataSet
     * @covers \Lcobucci\JWT\Token\Signature
     * @covers \Lcobucci\JWT\Signer\None
     * @covers \Lcobucci\JWT\Signer\Key
     */
    public function parserCanReadAToken(Token $generated): void
    {
        self::markTestSkipped('API being refactored');

        $read = $this->config->getParser()->parse((string) $generated);

        self::assertEquals($generated, $read);
        self::assertEquals('testing', $read->claims()->get('user')['name']);
    }

    /**
     * @test
     *
     * @depends builderCanGenerateAToken
     *
     * @covers \Lcobucci\JWT\Configuration
     * @covers \Lcobucci\JWT\Token\Builder
     * @covers \Lcobucci\JWT\Token\Parser
     * @covers \Lcobucci\JWT\Token\Plain
     * @covers \Lcobucci\JWT\Token\DataSet
     * @covers \Lcobucci\JWT\Token\Signature
     * @covers \Lcobucci\JWT\Signer\None
     * @covers \Lcobucci\JWT\Signer\Key
     * @covers \Lcobucci\JWT\Validation\Validator
     * @covers \Lcobucci\JWT\Validation\Constraint\IssuedBy
     * @covers \Lcobucci\JWT\Validation\Constraint\PermittedFor
     * @covers \Lcobucci\JWT\Validation\Constraint\IdentifiedBy
     * @covers \Lcobucci\JWT\Validation\Constraint\ValidAt
     */
    public function tokenValidationShouldPassWhenEverythingIsFine(Token $generated): void
    {
        self::markTestSkipped('API being refactored');

        $constraints = [
            new IdentifiedBy('1'),
            new PermittedFor('http://client.abc.com'),
            new IssuedBy('http://issuer.abc.com', 'http://api.abc.com'),
            new ValidAt(new \DateTimeImmutable('@' . self::CURRENT_TIME))
        ];

        self::assertTrue($this->config->getValidator()->validate($generated, ...$constraints));
    }

    /**
     * @test
     *
     * @depends builderCanGenerateAToken
     *
     * @covers \Lcobucci\JWT\Configuration
     * @covers \Lcobucci\JWT\Token\Builder
     * @covers \Lcobucci\JWT\Token\Parser
     * @covers \Lcobucci\JWT\Token\Plain
     * @covers \Lcobucci\JWT\Token\DataSet
     * @covers \Lcobucci\JWT\Token\Signature
     * @covers \Lcobucci\JWT\Signer\None
     * @covers \Lcobucci\JWT\Signer\Key
     * @covers \Lcobucci\JWT\Validation\Validator
     */
    public function tokenValidationShouldAllowCustomConstraint(Token $generated): void
    {
        self::assertTrue($this->config->getValidator()->validate($generated, $this->validUserConstraint()));
    }

    /**
     * @test
     *
     * @expectedException \Lcobucci\JWT\Validation\InvalidTokenException
     *
     * @depends builderCanGenerateAToken
     *
     * @covers \Lcobucci\JWT\Configuration
     * @covers \Lcobucci\JWT\Token\Builder
     * @covers \Lcobucci\JWT\Token\Parser
     * @covers \Lcobucci\JWT\Token\Plain
     * @covers \Lcobucci\JWT\Token\DataSet
     * @covers \Lcobucci\JWT\Token\Signature
     * @covers \Lcobucci\JWT\Signer\None
     * @covers \Lcobucci\JWT\Signer\Key
     * @covers \Lcobucci\JWT\Validation\Validator
     * @covers \Lcobucci\JWT\Validation\InvalidTokenException
     * @covers \Lcobucci\JWT\Validation\Constraint\IssuedBy
     * @covers \Lcobucci\JWT\Validation\Constraint\IdentifiedBy
     */
    public function tokenAssertionShouldRaiseExceptionWhenOneOfTheConstraintsFails(Token $generated): void
    {
        $constraints = [
            new IdentifiedBy('1'),
            new IssuedBy('http://issuer.abc.com')
        ];

        $this->config->getValidator()->assert($generated, ...$constraints);
    }

    private function validUserConstraint(): Constraint
    {
        return new class() implements Constraint
        {
            public function assert(Token $token): void
            {
                if (!$token instanceof Token\Plain) {
                    throw new ConstraintViolationException();
                }

                $claims = $token->claims();

                if (!$claims->has('user')) {
                    throw new ConstraintViolationException();
                }

                $user = $claims->get('user');

                if (empty($user['name']) || empty($user['email'])) {
                    throw new ConstraintViolationException();
                }
            }
        };
    }
}
