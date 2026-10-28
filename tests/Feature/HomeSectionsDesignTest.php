<?php

namespace Tests\Feature;

use App\Models\HomeSection;
use Database\Seeders\StationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeSectionsDesignTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_section_types_render(): void
    {
        $this->seed(StationSeeder::class);
        $add = fn (string $type, array $settings = [], array $extra = []) => HomeSection::query()->create(['type' => $type, 'settings' => $settings, 'is_active' => true, 'sort' => HomeSection::count() + 1] + $extra);

        $add('media', ['style' => ['bg' => 'night', 'pad' => 'large', 'title_size' => 'xl']]);
        $add('app');
        $add('stats', ['items' => [['value' => '12', 'label' => 'σταθμοί FM']]]);
        $add('steps');
        $add('faq', ['items' => [['q' => 'Πόσο κοστίζει ένα σποτ;', 'a' => 'Δείτε τον τιμοκατάλογο μετά την εγγραφή.']]]);
        $add('video', ['url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ']);
        $add('contact');
        $add('logos'); // χωρίς λογότυπα: δεν εμφανίζεται

        $html = $this->get('/')->assertOk()
            ->assertSee('Ακούγεσαι.')->assertSee('Βγάλτε προσφορά')
            ->assertSee('apps.apple.com/us/app/on-air-media-group', false)->assertSee('play.google.com/store/apps/details?id=gr.radio.onairmediagroup', false)
            ->assertSee('Σκανάρετε')
            ->assertSee('σταθμοί FM')->assertSee('Πώς λειτουργεί')
            ->assertSee('"@type":"FAQPage"', false)
            ->assertSee('youtube-nocookie.com/embed/dQw4w9WgXcQ', false)
            ->assertSee('Μιλήστε μαζί μας')
            ->assertDontSee('data-section="logos"', false)
            ->getContent();

        // Ρυθμίσεις εμφάνισης στο περιτύλιγμα
        $this->assertMatchesRegularExpression('/data-section="media"[^>]*data-pad="large"[^>]*data-title="xl"/', preg_replace('/\s+/', ' ', $html));
        $this->assertStringContainsString('sec-tone-light', $html);
        $this->assertStringContainsString('background: #121a33', $html);
    }
}
