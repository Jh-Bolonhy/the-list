<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Element;
use Illuminate\Support\Facades\DB;

class CheckElementCycles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'elements:check-cycles {--user-id= : Check cycles for specific user ID} {--fix : Automatically fix cycles by setting parent to null}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for circular dependencies in element hierarchy (parent cannot be descendant of its children). Use --fix to automatically fix by setting parent to null.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userId = $this->option('user-id');
        
        $query = Element::query();
        if ($userId) {
            $query->where('user_id', $userId);
        }
        
        $elements = $query->get();
        
        if ($elements->isEmpty()) {
            $this->info('No elements found in database.');
            return 0;
        }

        $this->info("Checking {$elements->count()} elements for circular dependencies...\n");

        $cycles = [];
        $checked = [];

        foreach ($elements as $element) {
            if (isset($checked[$element->id])) {
                continue;
            }

            $cycle = $this->detectCycle($element, $elements);
            if ($cycle) {
                $cycles[] = $cycle;
                // Mark all elements in cycle as checked
                foreach ($cycle as $cycleElement) {
                    $checked[$cycleElement['id']] = true;
                }
            } else {
                $checked[$element->id] = true;
            }
        }

        if (empty($cycles)) {
            $this->info('✓ No circular dependencies found. All parent-child relationships are valid.');
            return 0;
        }

        $this->error("✗ Found " . count($cycles) . " circular dependency(ies):\n");
        
        $fixedCount = 0;
        
        foreach ($cycles as $index => $cycle) {
            $this->warn("Cycle #" . ($index + 1) . ":");
            
            if (count($cycle) > 0) {
                $firstElement = $cycle[0];
                
                $this->line("  Element #{$firstElement['id']} ({$firstElement['title']})");
                $this->line("    has parent: Element #{$firstElement['parent_element_id']}");
                $this->line("    BUT this parent is a descendant of Element #{$firstElement['id']}");
                
                // Build path string with IDs only
                $pathIds = array_map(function($cycleElement) {
                    return $cycleElement['id'];
                }, $cycle);
                
                // Add parent ID at the end to complete the cycle
                if (count($cycle) > 0) {
                    $pathIds[] = $firstElement['id'];
                }
                
                $this->line("  Path through descendants: " . implode(' > ', $pathIds));

                // Fix cycle if --fix option is set
                if ($this->option('fix')) {
                    $elementToFix = Element::find($firstElement['id']);
                    if ($elementToFix) {
                        $oldParentId = $elementToFix->parent_element_id;
                        $elementToFix->parent_element_id = null;
                        $elementToFix->save();
                        
                        $this->info("  ✓ Fixed: Set parent_element_id to null for Element #{$firstElement['id']} (was: {$oldParentId})");
                        $fixedCount++;
                    }
                }
            }
            
            $this->newLine();
        }

        if ($this->option('fix') && $fixedCount > 0) {
            $this->info("✓ Fixed {$fixedCount} circular dependency(ies) by setting parent to null.");
            $this->info("  Run the command again to verify all cycles are resolved.");
        } else if ($this->option('fix')) {
            $this->warn("  No cycles were fixed. This may indicate a data issue.");
        } else {
            $this->info("  Use --fix option to automatically fix cycles by setting parent to null.");
        }

        return $this->option('fix') && $fixedCount > 0 ? 0 : 1;
    }

    /**
     * Detect cycle: check if element's parent is one of its descendants
     * 
     * @param Element $element
     * @param \Illuminate\Database\Eloquent\Collection $allElements
     * @return array|null Cycle path if found, null otherwise
     */
    private function detectCycle(Element $element, $allElements)
    {
        if (!$element->parent_element_id) {
            return null; // Root element, no cycle possible
        }

        // Collect all descendants of this element (BFS)
        $descendants = $this->collectDescendants($element->id, $allElements);
        
        // Check if parent is in descendants (cycle detected)
        if (in_array($element->parent_element_id, $descendants)) {
            // Build cycle path
            $elementsById = $allElements->keyBy('id');
            $path = $this->buildCyclePath($element, $elementsById);
            return $path;
        }

        return null;
    }

    /**
     * Collect all descendant IDs using BFS
     * 
     * @param int $rootId
     * @param \Illuminate\Database\Eloquent\Collection $allElements
     * @return array
     */
    private function collectDescendants($rootId, $allElements)
    {
        $descendants = [];
        $frontier = [$rootId];
        $visited = [$rootId => true];

        while (!empty($frontier)) {
            $nextFrontier = [];
            
            foreach ($frontier as $parentId) {
                $children = $allElements->where('parent_element_id', $parentId);
                
                foreach ($children as $child) {
                    if (!isset($visited[$child->id])) {
                        $descendants[] = $child->id;
                        $nextFrontier[] = $child->id;
                        $visited[$child->id] = true;
                    }
                }
            }
            
            $frontier = $nextFrontier;
        }

        return $descendants;
    }

    /**
     * Build cycle path showing the circular dependency
     * 
     * @param Element $element
     * @param \Illuminate\Support\Collection $elementsById
     * @return array
     */
    private function buildCyclePath(Element $element, $elementsById)
    {
        $path = [];
        
        // Start with the element itself
        $path[] = [
            'id' => $element->id,
            'title' => $element->title,
            'parent_element_id' => $element->parent_element_id
        ];

        // Find path from element to its parent (through descendants) using DFS
        $parentId = $element->parent_element_id;
        $pathToParent = [];
        $visited = [];
        
        if ($this->findPathToParent($element->id, $parentId, $elementsById, $visited, $pathToParent)) {
            // findPathToParent already includes the parent element in the path
            $path = array_merge($path, $pathToParent);
        }

        return $path;
    }

    /**
     * Find path from element to target parent through descendants (DFS)
     * 
     * @param int $currentId
     * @param int $targetId
     * @param \Illuminate\Support\Collection $elementsById
     * @param array $visited
     * @param array $path
     * @return bool
     */
    private function findPathToParent($currentId, $targetId, $elementsById, &$visited, &$path)
    {
        if ($currentId == $targetId) {
            // Target found - it should already be in path (added as a child)
            return true;
        }

        if (isset($visited[$currentId])) {
            return false;
        }

        $visited[$currentId] = true;
        $currentElement = $elementsById->get($currentId);
        
        if (!$currentElement) {
            return false;
        }

        // Check all children
        $children = $elementsById->where('parent_element_id', $currentId);
        foreach ($children as $child) {
            // If this child is the target, add it and return immediately
            if ($child->id == $targetId) {
                $path[] = [
                    'id' => $child->id,
                    'title' => $child->title,
                    'parent_element_id' => $child->parent_element_id
                ];
                return true;
            }
            
            $path[] = [
                'id' => $child->id,
                'title' => $child->title,
                'parent_element_id' => $child->parent_element_id
            ];
            
            if ($this->findPathToParent($child->id, $targetId, $elementsById, $visited, $path)) {
                return true;
            }
            
            array_pop($path);
        }

        return false;
    }
}

