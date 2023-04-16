<?php

namespace blink\expression\expr;

class OrExpr extends Expr
{
    /**
     * @var Expr[]
     */
    public array $exprs;

    public function __construct(Expr ...$expressions)
    {
        $this->exprs = $expressions;
    }

    public function add(Expr $expression): self
    {
        $this->exprs[] = $expression;
        return $this;
    }

    public static function type(): string
    {
        return 'or';
    }

    public function toArray(): array
    {
        return parent::toArray() + [
            'exprs' => $this->exprs,
        ];
    }
}
