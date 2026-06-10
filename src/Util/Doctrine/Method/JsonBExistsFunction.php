<?php

declare(strict_types=1);

namespace Nektria\Util\Doctrine\Method;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\QueryException;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\TokenType;

class JsonBExistsFunction extends FunctionNode
{
    private mixed $field;

    private mixed $value;

    public function getSql(SqlWalker $sqlWalker): string
    {
        return 'jsonb_exists(' . $this->field->dispatch($sqlWalker) . ',' . $this->value->dispatch($sqlWalker) . ')';
    }

    /**
     * @throws QueryException
     */
    public function parse(Parser $parser): void
    {
        $parser->match(TokenType::T_IDENTIFIER);
        $parser->match(TokenType::T_OPEN_PARENTHESIS);
        $this->field = $parser->StringPrimary();
        $parser->match(TokenType::T_COMMA);
        $this->value = $parser->StringPrimary();
        $parser->match(TokenType::T_CLOSE_PARENTHESIS);
    }
}
