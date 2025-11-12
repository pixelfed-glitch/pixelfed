<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPrivateMethodRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPromotedPropertyRector;
use Rector\DeadCode\Rector\Property\RemoveUnusedPrivatePropertyRector;
use Rector\DeadCode\Rector\Assign\RemoveUnusedVariableAssignRector;
use Rector\DeadCode\Rector\If_\RemoveDeadInstanceOfRector;
use Rector\DeadCode\Rector\StmtsAwareInterface\RemoveJustPropertyFetchForAssignRector;
use Rector\DeadCode\Rector\Stmt\RemoveUnreachableStatementRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveUselessParamTagRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveUselessReturnTagRector;
use Rector\DeadCode\Rector\Node\RemoveNonExistingVarAnnotationRector;
use Rector\DeadCode\Rector\Concat\RemoveConcatAutocastRector;
use Rector\DeadCode\Rector\Return_\RemoveDeadConditionAboveReturnRector;
use Rector\DeadCode\Rector\For_\RemoveDeadLoopRector;
use Rector\DeadCode\Rector\Foreach_\RemoveUnusedForeachKeyRector;
use Rector\DeadCode\Rector\TryCatch\RemoveDeadTryCatchRector;
use Rector\DeadCode\Rector\Cast\RecastingRemovalRector;
use Rector\DeadCode\Rector\Expression\RemoveDeadStmtRector;
use Rector\DeadCode\Rector\If_\RemoveAlwaysTrueIfConditionRector;
use Rector\DeadCode\Rector\If_\SimplifyIfElseWithSameContentRector;
use Rector\DeadCode\Rector\Switch_\RemoveDuplicatedCaseInSwitchRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/app',
        __DIR__ . '/bootstrap',
        __DIR__ . '/config',
        __DIR__ . '/public',
        __DIR__ . '/resources',
        __DIR__ . '/routes',
        __DIR__ . '/tests',
    ])
    ->withRules([
        // UNUSED PRIVATE METHODS & PROPERTIES
        RemoveUnusedPrivateMethodRector::class,              // Remove unused private methods
        // RemoveUnusedPrivatePropertyRector::class,            // Remove unused private properties
        /// RemoveUnusedPromotedPropertyRector::class,           // Remove unused promoted properties (PHP 8.0+)
         
        // UNUSED VARIABLES & ASSIGNMENTS
        // RemoveUnusedVariableAssignRector::class,             // Remove unused variable assignments
        // RemoveJustPropertyFetchForAssignRector::class,       // Remove property fetch that's only assigned but never used
        /// RemoveUnusedForeachKeyRector::class,                 // Remove unused foreach keys
        
        // UNREACHABLE & DEAD CODE
        // RemoveUnreachableStatementRector::class,             // Remove unreachable statements after return/throw
        /// RemoveDeadStmtRector::class,                         // Remove dead statements
        ///  RemoveDeadInstanceOfRector::class,                   // Remove dead instanceof checks
        /// RemoveDeadConditionAboveReturnRector::class,         // Remove dead conditions above return
        /// RemoveDeadLoopRector::class,                         // Remove loops that never execute
        /// RemoveDeadTryCatchRector::class,                     // Remove try-catch that never catches
        
        // USELESS ANNOTATIONS
        // RemoveUselessParamTagRector::class,                  // Remove useless @param tags
        /// RemoveUselessReturnTagRector::class,                 // Remove useless @return tags
        /// RemoveNonExistingVarAnnotationRector::class,         // Remove @var for non-existing variables
        
        // SIMPLIFY CONDITIONS
        /// RemoveAlwaysTrueIfConditionRector::class,            // Remove always-true if conditions
        /// SimplifyIfElseWithSameContentRector::class,          // Simplify if-else with same content
        /// RemoveDuplicatedCaseInSwitchRector::class,           // Remove duplicated switch cases
        

        
        // UNNECESSARY OPERATIONS
        /// RemoveConcatAutocastRector::class,                   // Remove unnecessary string concatenation autocasts
        /// RecastingRemovalRector::class,                       // Remove unnecessary type recasting
    ]);
