# CONFLICTS

This document explains why certain conflicts were added to `composer.json` and references related issues.

- `api-platform/core:2.7.17`:

  This version introduced class aliases, which lead to a fatal error:
  `The autoloader expected class "ApiPlatform\Core\Bridge\Symfony\Bundle\DependencyInjection\ApiPlatformExtension" to be defined in file ".../vendor/api-platform/core/src/Core/Bridge/Symfony/Bundle/DependencyInjection/ApiPlatformExtension.php". The file was found but the class was not in it, the class name or namespace probably has a typo.`

- `lexik/jwt-authentication-bundle: ^2.18`

  After bumping to this version ApiBundle starts failing due to requesting a non-existing `api_platform.openapi.factory.legacy` service.
  As we are not using this service across the ApiBundle we added this conflict to unlock the builds, until we investigate the problem.

- `symfony/serializer:^6.4.23`:

  This version introduces a change in method signature that is not compatible with API Platform 2.7 and leads to a fatal error:
  `PHP Fatal error:  Declaration of ApiPlatform\Serializer\AbstractItemNormalizer::getAllowedAttributes($classOrObject, array $context, $attributesAsString = false) must be compatible with Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer::getAllowedAttributes(object|string $classOrObject, array $context, bool $attributesAsString = false): array|bool in /home/runner/work/Sylius/Sylius/vendor/api-platform/core/src/Serializer/AbstractItemNormalizer.php on line 493`
