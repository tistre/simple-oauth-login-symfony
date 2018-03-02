# simple-oauth-login-symfony

Very basic example for how to replace your Symfony login form with OAuth2.
No login form and no knowledge of passwords mean less security holes!

You’ll want to use [HWIOAuthBundle](https://github.com/hwi/HWIOAuthBundle) or [oauth2-client-bundle](https://github.com/knpuniversity/oauth2-client-bundle)
instead. I only made this because I couldn’t get either of those to work.

Built on [Symfony](https://symfony.com) 3.4,
the [Symfony Guard component](https://symfony.com/doc/3.4/security/guard_authentication.html)
and [League/oauth2-client](http://oauth2-client.thephpleague.com)
(wrapped in my [simple-oauth-login](https://github.com/tistre/simple-oauth-login)).

Bonus feature: An easily configurable way to bypass / fake the OAuth2 login for your local development setup.