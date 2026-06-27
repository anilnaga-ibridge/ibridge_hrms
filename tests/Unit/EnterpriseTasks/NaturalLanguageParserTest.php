<?php

namespace Tests\Unit\EnterpriseTasks;

use Tests\TestCase;
use App\Models\EnterpriseTasks\Label;
use App\Services\EnterpriseTasks\NaturalLanguageParserService;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class NaturalLanguageParserTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_correctly_parses_priorities()
    {
        $parser = new NaturalLanguageParserService();
        
        $res = $parser->parse('Fix code tomorrow #P1', 1);
        $this->assertEquals('P1', $res['priority']);

        $res = $parser->parse('Normal task #P3', 1);
        $this->assertEquals('P3', $res['priority']);
    }

    /** @test */
    public function it_correctly_parses_labels()
    {
        $companyId = 1;
        
        // Create matching labels in DB for company_id = 1
        $labelBug = Label::create(['company_id' => $companyId, 'name' => 'bug', 'color' => '#ef4444']);
        $labelFront = Label::create(['company_id' => $companyId, 'name' => 'frontend', 'color' => '#3b82f6']);

        $parser = new NaturalLanguageParserService();
        
        $res = $parser->parse('Test task with labels #bug #frontend', $companyId);
        
        $this->assertContains($labelBug->xid, $res['labels']);
        $this->assertContains($labelFront->xid, $res['labels']);
    }
}
