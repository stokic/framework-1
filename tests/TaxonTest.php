<?php
/**
 * Contains the TaxonTest class.
 *
 * @copyright   Copyright (c) 2018 Attila Fulop
 * @author      Attila Fulop
 * @license     MIT
 * @since       2018-08-27
 *
 */

namespace Vanilo\Category\Tests;

use Vanilo\Category\Models\Taxon;
use Vanilo\Category\Models\Taxonomy;

class TaxonTest extends TestCase
{
    /** @test */
    public function taxons_must_belong_to_a_taxonomy()
    {
        $this->expectExceptionMessageRegExp('/NOT NULL constraint failed: taxons\.taxonomy_id/');

        Taxon::create();
    }

    /** @test */
    public function taxonomy_can_be_assigned_with_settaxonomy_method()
    {
        $taxonomy = Taxonomy::create(['name' => 'Regions']);

        $taxon = new Taxon();
        $taxon->setTaxonomy($taxonomy);
        $taxon->name = 'Tokaj';
        $taxon->save();

        $this->assertEquals($taxonomy->id, $taxon->taxonomy->id);
    }

    /** @test */
    public function taxons_must_have_a_name()
    {
        $taxonomy = Taxonomy::create(['name' => 'Category']);

        $this->expectExceptionMessageRegExp('/NOT NULL constraint failed: taxons\.name/');

        Taxon::create(['taxonomy_id' => $taxonomy->id]);
    }

    /** @test */
    public function slug_is_autogenerated_from_name()
    {
        $taxonomy = Taxonomy::create(['name' => 'Category']);

        $taxon = Taxon::create(['taxonomy_id' => $taxonomy->id, 'name' => 'Example Taxon']);

        $this->assertEquals('example-taxon', $taxon->slug);
    }

    /** @test */
    public function slug_can_be_explicitly_set()
    {
        $taxon = Taxon::create([
            'taxonomy_id' => Taxonomy::create(['name' => 'Wine Regions']),
            'name'        => 'Carcavelos DOC',
            'slug'        => 'carcavelos'
        ]);

        $this->assertEquals('carcavelos', $taxon->slug);
    }

    /** @test */
    public function same_slug_can_be_used_in_another_taxonomy()
    {
        $taxonomy1 = Taxonomy::create(['name' => 'Category']);
        $taxonomy2 = Taxonomy::create(['name' => 'Regions']);

        $taxon1 = Taxon::create([
            'taxonomy_id' => $taxonomy1->id,
            'name'        => 'Domestic'
        ]);

        $taxon2 = Taxon::create([
            'taxonomy_id' => $taxonomy2->id,
            'name'        => 'Domestic'
        ]);

        $this->assertEquals('domestic', $taxon1->slug);
        $this->assertEquals('domestic', $taxon2->slug);
    }

    /** @test */
    public function same_slug_can_be_used_in_another_level_of_the_same_taxonomy()
    {
        $taxonomy = Taxonomy::create(['name' => 'Category']);

        $taxon1 = Taxon::create([
            'name'        => 'Docking Stations',
            'taxonomy_id' => $taxonomy->id
        ]);

        $taxon2 = Taxon::create([
            'parent_id'   => $taxon1->id,
            'name'        => 'Docking Stations',
            'taxonomy_id' => $taxonomy->id
        ]);

        $this->assertEquals($taxon1->slug, $taxon2->slug);
    }

    /** @test */
    public function slugs_must_be_unique_within_the_same_level_of_a_taxonomy()
    {
        $this->expectExceptionMessageRegExp('/UNIQUE constraint failed/');

        $taxonomy = Taxonomy::create(['name' => 'Category']);
        $root     = Taxon::create([
            'name'        => 'Accessories',
            'taxonomy_id' => $taxonomy->id
        ]);

        $taxon1 = Taxon::create([
            'parent_id'   => $root->id,
            'name'        => 'Docking Stations',
            'taxonomy_id' => $taxonomy->id
        ]);

        $taxon2 = Taxon::create([
            'parent_id'   => $root->id,
            'name'        => 'Docking Stations',
            'taxonomy_id' => $taxonomy->id
        ]);

        $this->assertEquals($taxon1->slug, $taxon2->slug);
    }

    /** @test */
    public function taxons_belong_to_a_taxonomy()
    {
        $taxonomy = Taxonomy::create(['name' => 'Category']);

        $taxon = Taxon::create(['taxonomy_id' => $taxonomy->id, 'name' => 'Taxon']);

        $this->assertInstanceOf(Taxonomy::class, $taxon->taxonomy);
        $this->assertEquals($taxonomy->id, $taxon->taxonomy->id);
    }

    /** @test */
    public function taxons_parent_is_optional()
    {
        $taxonomy = Taxonomy::create(['name' => 'Category']);

        $taxon = Taxon::create(['name' => 'Parent', 'taxonomy_id' => $taxonomy->id]);

        $this->assertNull($taxon->parent);
    }

    /** @test */
    public function taxons_can_have_a_parent()
    {
        $taxonomy = Taxonomy::create(['name' => 'Category']);

        $taxon = Taxon::create(['name' => 'Parent', 'taxonomy_id' => $taxonomy->id]);

        $child = Taxon::create([
            'name'        => 'Child',
            'parent_id'   => $taxon->id,
            'taxonomy_id' => $taxonomy->id
        ]);

        $this->assertEquals($taxon->id, $child->parent->id);
    }

    /** @test */
    public function taxons_can_have_children()
    {
        $taxonomy = Taxonomy::create(['name' => 'Category']);

        $taxon = Taxon::create(['name' => 'Parent', 'taxonomy_id' => $taxonomy->id]);

        Taxon::create([
            'name'        => 'Child 1',
            'parent_id'   => $taxon->id,
            'taxonomy_id' => $taxonomy->id
        ]);

        Taxon::create([
            'name'        => 'Child 2',
            'parent_id'   => $taxon->id,
            'taxonomy_id' => $taxonomy->id
        ]);

        Taxon::create([
            'name'        => 'Child 3',
            'parent_id'   => $taxon->id,
            'taxonomy_id' => $taxonomy->id
        ]);

        $this->assertCount(3, $taxon->children);
    }

    /** @test */
    public function taxons_can_tell_their_level_in_the_tree()
    {
        $taxonomy = Taxonomy::create(['name' => 'Category']);

        $root1 = Taxon::create(['name' => 'Root 1', 'taxonomy_id' => $taxonomy->id]);
        $root2 = Taxon::create(['name' => 'Root 2', 'taxonomy_id' => $taxonomy->id]);

        $root1Child1 = Taxon::create([
            'name'        => 'Root 1 Child 1',
            'parent_id'   => $root1->id,
            'taxonomy_id' => $taxonomy->id
        ]);

        $root1Child2 = Taxon::create([
            'name'        => 'Root 1 Child 2',
            'parent_id'   => $root1->id,
            'taxonomy_id' => $taxonomy->id
        ]);

        $root1Child2Child1 = Taxon::create([
            'name'        => 'Root 1 Child 2 Child 1',
            'parent_id'   => $root1Child2->id,
            'taxonomy_id' => $taxonomy->id
        ]);

        $root1Child2Child1Child1 = Taxon::create([
            'name'        => 'Root 1 Child 2 Child 1 Child 1',
            'parent_id'   => $root1Child2Child1->id,
            'taxonomy_id' => $taxonomy->id
        ]);

        $root2Child1 =Taxon::create([
            'name'        => 'Root 2 Child 1',
            'parent_id'   => $root2->id,
            'taxonomy_id' => $taxonomy->id
        ]);

        $this->assertEquals(0, $root1->level);

        $this->assertEquals(1, $root1Child1->level);
        $this->assertEquals(1, $root1Child2->level);

        $this->assertEquals(2, $root1Child2Child1->level);
        $this->assertEquals(3, $root1Child2Child1Child1->level);

        $this->assertEquals(0, $root2->level);

        $this->assertEquals(1, $root2Child1->level);
    }

    /** @test */
    public function collection_of_parents_can_be_returned()
    {
        $taxonomy = Taxonomy::create(['name' => 'Category']);

        $root = Taxon::create(['name' => 'root', 'taxonomy_id' => $taxonomy->id]);

        $child1 = Taxon::create([
            'name'        => 'child_1',
            'parent_id'   => $root->id,
            'taxonomy_id' => $taxonomy->id
        ]);

        $child2 = Taxon::create([
            'name'        => 'child_2',
            'parent_id'   => $child1->id,
            'taxonomy_id' => $taxonomy->id
        ]);

        $child3 = Taxon::create([
            'name'        => 'child_3',
            'parent_id'   => $child2->id,
            'taxonomy_id' => $taxonomy->id
        ]);

        $this->assertEmpty($root->parents);

        $this->assertCount(1, $child1->parents);
        $this->assertArrayHasKey('root', $child1->parents->keyBy('name'));

        $this->assertCount(2, $child2->parents);
        $this->assertArrayHasKey('root', $child2->parents->keyBy('name'));
        $this->assertArrayHasKey('child_1', $child2->parents->keyBy('name'));

        $this->assertCount(3, $child3->parents);
        $this->assertArrayHasKey('root', $child3->parents->keyBy('name'));
        $this->assertArrayHasKey('child_1', $child3->parents->keyBy('name'));
        $this->assertArrayHasKey('child_2', $child3->parents->keyBy('name'));
    }

    /** @test */
    public function changing_the_parent_gets_reflected_in_the_parents_collection()
    {
        $taxonomy = Taxonomy::create(['name' => 'Category']);

        $root = Taxon::create(['name' => 'root', 'taxonomy_id' => $taxonomy->id]);

        $child1 = Taxon::create([
            'name'        => 'child_1',
            'parent_id'   => $root->id,
            'taxonomy_id' => $taxonomy->id
        ]);

        $child2 = Taxon::create([
            'name'        => 'child_2',
            'parent_id'   => $child1->id,
            'taxonomy_id' => $taxonomy->id
        ]);

        $this->assertCount(1, $child1->parents);
        $this->assertArrayHasKey('root', $child1->parents->keyBy('name'));

        $child1->removeParent();
        $this->assertCount(0, $child1->parents);

        $child2->setParent($root);

        $this->assertCount(1, $child2->parents);
        $this->assertArrayHasKey('root', $child2->parents->keyBy('name'));
        $this->assertArrayNotHasKey('child_1', $child2->parents->keyBy('name'));
    }

    /** @test */
    public function changing_the_parent_recalculates_the_level()
    {
        $taxonomy = Taxonomy::create(['name' => 'Category']);

        $root = Taxon::create(['name' => 'root', 'taxonomy_id' => $taxonomy->id]);

        $child1 = Taxon::create([
            'name'        => 'child_1',
            'parent_id'   => $root->id,
            'taxonomy_id' => $taxonomy->id
        ]);

        $child2 = Taxon::create([
            'name'        => 'child_2',
            'parent_id'   => $child1->id,
            'taxonomy_id' => $taxonomy->id
        ]);

        $this->assertEquals(1, $child1->level);
        $this->assertEquals(2, $child2->level);

        $child1->removeParent();
        $this->assertEquals(0, $child1->level);

        $child2->setParent($root);
        $this->assertEquals(1, $child2->level);
    }

    /** @test */
    public function taxons_can_tell_if_they_are_root_level_ones()
    {
        $taxonomy = Taxonomy::create(['name' => 'Category']);

        $root = Taxon::create(['name' => 'Parent', 'taxonomy_id' => $taxonomy->id]);

        $child = Taxon::create([
            'name'        => 'Child',
            'parent_id'   => $root->id,
            'taxonomy_id' => $taxonomy->id
        ]);

        $this->assertTrue($root->isRootLevel());
        $this->assertFalse($child->isRootLevel());

        $child->removeParent();
        $this->assertTrue($child->isRootLevel());
    }

    /** @test */
    public function the_by_taxonomy_scope_can_return_taxons_by_taxonomy_object()
    {
        $category = Taxonomy::create(['name' => 'Category']);

        Taxon::create(['name' => 'Cat 1', 'taxonomy_id' => $category->id]);
        Taxon::create(['name' => 'Cat 2', 'taxonomy_id' => $category->id]);
        Taxon::create(['name' => 'Cat 3', 'taxonomy_id' => $category->id]);

        $brand = Taxonomy::create(['name' => 'Brand']);

        Taxon::create(['name' => 'Brand 1', 'taxonomy_id' => $brand->id]);
        Taxon::create(['name' => 'Brand 2', 'taxonomy_id' => $brand->id]);
        Taxon::create(['name' => 'Brand 3', 'taxonomy_id' => $brand->id]);
        Taxon::create(['name' => 'Brand 4', 'taxonomy_id' => $brand->id]);

        $this->assertCount(3, Taxon::byTaxonomy($category)->get());
        $this->assertCount(4, Taxon::byTaxonomy($brand)->get());
    }

    /** @test */
    public function the_by_taxonomy_scope_can_return_taxons_by_taxonomy_id()
    {
        $gadgets = Taxonomy::create(['name' => 'Gadgets']);

        Taxon::create(['name' => 'Smartphones', 'taxonomy_id' => $gadgets->id]);
        Taxon::create(['name' => 'Smartwatches', 'taxonomy_id' => $gadgets->id]);

        $brand = Taxonomy::create(['name' => 'Brand']);

        Taxon::create(['name' => 'Brand X', 'taxonomy_id' => $brand->id]);
        Taxon::create(['name' => 'Brand Y', 'taxonomy_id' => $brand->id]);
        Taxon::create(['name' => 'Brand Z', 'taxonomy_id' => $brand->id]);

        $this->assertCount(2, Taxon::byTaxonomy($gadgets->id)->get());
        $this->assertCount(3, Taxon::byTaxonomy($brand->id)->get());
    }

    /** @test */
    public function can_be_sorted_by_priority()
    {
        $category = Taxonomy::create(['name' => 'Category']);

        Taxon::create(['name' => 'Cat 1', 'taxonomy_id' => $category->id, 'priority' => 27]);
        Taxon::create(['name' => 'Cat 2', 'taxonomy_id' => $category->id, 'priority' => 83]);
        Taxon::create(['name' => 'Cat 3', 'taxonomy_id' => $category->id, 'priority' => 3]);

        $taxons = Taxon::byTaxonomy($category)->sort()->get();

        $this->assertEquals('Cat 3', $taxons[0]->name);
        $this->assertEquals('Cat 1', $taxons[1]->name);
        $this->assertEquals('Cat 2', $taxons[2]->name);
    }
}
