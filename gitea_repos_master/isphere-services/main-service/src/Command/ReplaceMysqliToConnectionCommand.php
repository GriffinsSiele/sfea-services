<?php

declare(strict_types=1);

namespace App\Command;

use PhpParser\Comment\Doc;
use PhpParser\Error;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NodeConnectingVisitor;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:replace-mysql-to-connection', hidden: true)]
class ReplaceMysqliToConnectionCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addArgument('filename', InputArgument::REQUIRED)
            ->addOption('revert', mode: InputOption::VALUE_NONE);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $filename = $input->getArgument('filename');
        $filenameBackup = $filename.'.~';

        if (!\file_exists($filename)) {
            $io->error(\sprintf('Cannot access %s', $filename));

            return self::FAILURE;
        }

        if ($input->getOption('revert')) {
            if (!\file_exists($filenameBackup)) {
                $io->error('Backup file lost, sorry');

                return self::FAILURE;
            }

            \copy($filenameBackup, $filename);

            return self::SUCCESS;
        }

        $parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);

        try {
            $ast = $parser->parse(\file_get_contents($filename));
        } catch (Error $error) {
            $io->error($error->getMessage());

            return self::FAILURE;
        }

        $traverser = new NodeTraverser();
        $traverser->addVisitor(new NodeConnectingVisitor());
        $traverser->addVisitor(
            new class() extends NodeVisitorAbstract {
                public function leaveNode(Node $node): ?array
                {
                    if ($node instanceof Node\Stmt\Global_) {
                        $expressions = [];

                        foreach ($node->vars as $var) {
                            if (!isset($var->name)) {
                                continue;
                            }

                            $name = $var->name;

                            if ('mysqli' === $name) {
                                $name = 'connection';
                            }

                            $parent = $node;

                            while ($parent = $parent->getAttribute('parent')) {
                                if ($parent instanceof Node\Stmt\ClassMethod) {
                                    foreach ($parent->params as $param) {
                                        if ('params' === $param->var->name) {
                                            break 2;
                                        }
                                    }

                                    \array_unshift(
                                        $parent->params,
                                        new Node\Param(
                                            new Node\Expr\Variable('params'),
                                            type: 'array',
                                        )
                                    );

                                    break;
                                }
                            }

                            $expression = new Node\Stmt\Expression(
                                new Node\Expr\Assign(
                                    $var, new Node\Expr\ArrayDimFetch(
                                        new Node\Expr\Variable('params'),
                                        new Node\Scalar\String_('_'.$name),
                                    )
                                ),
                            );

                            if ('connection' === $name) {
                                $expression->setDocComment(
                                    new Doc('/** @var \Doctrine\DBAL\Connection $'.$var->name.' */')
                                );
                            }

                            $expressions[] = $expression;
                        }

                        return $expressions;
                    } elseif ($node instanceof Node\Stmt\Expression) {
                        if ($node->expr instanceof Node\Expr\MethodCall
                            && 'mysqli' === $node->expr->var->name
                        ) {
                            $node->expr->name = 'executeStatement';

                            return [$node];
                        } elseif ($node->expr instanceof Node\Expr\Assign
                            && $node->expr->expr instanceof Node\Expr\MethodCall
                            && 'mysqli' === ($node->expr->expr->var->name ?? '')
                        ) {
                            $node->expr->expr->name = 'executeQuery';

                            return [$node];
                        }
                    }

                    return null;
                }
            }
        );

        $ast = $traverser->traverse($ast);

        $prettyPrinter = new Standard();
        $php = $prettyPrinter->prettyPrintFile($ast);
        $php = \str_replace('isphere.', '', $php);
        $php = \str_replace('fetch_object', 'fetchAssociative', $php);
        $php = \str_replace('num_rows', 'rowCount()', $php);
        $php = \preg_replace('~(?:row\-\>)(\w+)~', 'row[\'$1\']', $php);

        \preg_match_all('~(\w+)\(array \$params~ms', $php, $mm);

        foreach ($mm[1] as $declaredMethodName) {
            $php = \preg_replace('~>'.$declaredMethodName.'\((?!\$params)~', '$0\$params', $php);
        }

        if (\file_exists($filenameBackup)) {
            if ($io->confirm(
                'Backup file "'.$filenameBackup.'" already exists. Are you want to replace it?',
                false
            )) {
                \copy($filename, $filenameBackup);
            }
        } else {
            \copy($filename, $filenameBackup);
        }

        \file_put_contents($filename, $php);

        return self::SUCCESS;
    }
}
