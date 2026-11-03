<?php

use Symfony\Component\Yaml\Yaml;

test('production services pull the same required application image without building', function () {
    $compose = Yaml::parseFile(dirname(__DIR__, 2).'/compose.yaml');

    foreach (['app', 'worker', 'scheduler', 'reverb'] as $serviceName) {
        $service = $compose['services'][$serviceName];

        expect($service)->not->toHaveKey('build')
            ->and($service['image'])->toStartWith('${APP_IMAGE:?')
            ->and($service['pull_policy'])->toBe('always')
            ->and($service['networks'])->toContain('backend');
    }
});

test('image publication requires app checks and browser smoke on release branches', function () {
    $workflow = Yaml::parseFile(dirname(__DIR__, 2).'/.github/workflows/ci.yml');
    $publication = $workflow['jobs']['publish-image'];

    expect($workflow['on']['push']['branches'])->toContain('main', 'production')
        ->and($publication['needs'])->toContain('checks', 'browser-smoke')
        ->and($publication['if'])->toContain("github.event_name == 'push'", "github.event_name == 'workflow_dispatch'", "github.ref == 'refs/heads/main'", "github.ref == 'refs/heads/production'")
        ->and($publication['permissions']['packages'])->toBe('write')
        ->and($workflow['permissions'])->not->toHaveKey('packages');
});

test('automatic deployment requires a published production image and explicit opt in', function () {
    $workflow = Yaml::parseFile(dirname(__DIR__, 2).'/.github/workflows/publish-image.yml');
    $deployment = $workflow['jobs']['deploy'];

    expect($workflow['on'])->toHaveKey('workflow_call')
        ->not->toHaveKey('pull_request')
        ->and($deployment['needs'])->toBe('publish')
        ->and($deployment['if'])->toBe("github.ref == 'refs/heads/production' && vars.DOKPLOY_DEPLOY_ENABLED == 'true'")
        ->and($deployment['env']['DOKPLOY_API_KEY'])->toBe('${{ secrets.DOKPLOY_API_KEY }}');
});

test('the image build receives the public Bugsink frontend DSN', function () {
    $workflow = Yaml::parseFile(dirname(__DIR__, 2).'/.github/workflows/publish-image.yml');
    $publication = $workflow['jobs']['publish'];
    $build = collect($publication['steps'])->firstWhere('name', 'Build application image');

    expect($publication['env']['VITE_SENTRY_DSN'] ?? null)->toBe('${{ vars.VITE_SENTRY_DSN }}')
        ->and($build['with']['build-args'])->toContain('VITE_SENTRY_DSN=${{ env.VITE_SENTRY_DSN }}');
});

test('all Laravel services receive optional backend Bugsink configuration', function () {
    $compose = Yaml::parseFile(dirname(__DIR__, 2).'/compose.yaml');

    foreach (['app', 'worker', 'scheduler', 'reverb'] as $serviceName) {
        $environment = $compose['services'][$serviceName]['environment'];

        expect($environment['SENTRY_LARAVEL_DSN'] ?? null)->toBe('${SENTRY_LARAVEL_DSN:-}')
            ->and($environment['SENTRY_TRACES_SAMPLE_RATE'] ?? null)->toBe('${SENTRY_TRACES_SAMPLE_RATE:-0}');
    }
});
