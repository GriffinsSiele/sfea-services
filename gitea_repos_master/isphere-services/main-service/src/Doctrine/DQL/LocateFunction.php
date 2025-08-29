<?php

declare(strict_types=1);

namespace App\Doctrine\DQL;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;

class LocateFunction extends FunctionNode
{
    private readonly Node $node1;
    private readonly Node $node2;

    public function parse(Parser $parser): void
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);

        $this->node1 = $parser->ArithmeticPrimary();

        $parser->match(Lexer::T_COMMA);

        $this->node2 = $parser->ArithmeticPrimary();

        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }

    public function getSql(SqlWalker $sqlWalker): string
    {
        return \sprintf(
            'LOCATE(%s, %s)',
            $this->node1->dispatch($sqlWalker),
            $this->node2->dispatch($sqlWalker),
        );
    }
}
