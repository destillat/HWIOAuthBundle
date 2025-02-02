<?php

/*
 * This file is part of the HWIOAuthBundle package.
 *
 * (c) Hardware.Info <opensource@hardware.info>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace HWI\Bundle\OAuthBundle\OAuth\ResourceOwner;

use Buzz\Message\RequestInterface as HttpRequestInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * FacebookResourceOwner
 *
 * @author Geoffrey Bachelet <geoffrey.bachelet@gmail.com>
 */
class FacebookResourceOwner extends GenericOAuth2ResourceOwner
{
    /**
     * {@inheritDoc}
     */
    protected $paths = array(
        'identifier' => 'id',
        'nickname'   => 'name',
        'firstname'   => 'first_name',
        'lastname'   => 'last_name',
        'realname'   => 'name',
        'email'      => 'email',
    );

    /**
     * {@inheritDoc}
     */
    public function getAuthorizationUrl($redirectUri, array $extraParameters = array())
    {
        $extraOptions = array();
        if (isset($this->options['display'])) {
            $extraOptions['display'] = $this->options['display'];
        }

        if (isset($this->options['auth_type'])) {
            $extraOptions['auth_type'] = $this->options['auth_type'];
        }

        return parent::getAuthorizationUrl($redirectUri, array_merge($extraOptions, $extraParameters));
    }

    /**
     * {@inheritDoc}
     */
    public function getAccessToken(Request $request, $redirectUri, array $extraParameters = array())
    {
        $parameters = array();
        if ($request->query->has('fb_source')) {
            $parameters['fb_source'] = $request->query->get('fb_source');
        }

        if ($request->query->has('fb_appcenter')) {
            $parameters['fb_appcenter'] = $request->query->get('fb_appcenter');
        }

        return parent::getAccessToken($request, $this->normalizeUrl($redirectUri, $parameters), $extraParameters);
    }

    /**
     * {@inheritDoc}
     */
    public function revokeToken($token)
    {
        $parameters = array(
            'client_id'     => $this->options['client_id'],
            'client_secret' => $this->options['client_secret'],
        );

        $response = $this->httpRequest($this->normalizeUrl($this->options['revoke_token_url'], array('token' => $token)), $parameters, array(), HttpRequestInterface::METHOD_POST);
        $response = $this->getResponseContent($response);

        return 'true' == $response;
    }

    /**
     * {@inheritDoc}
     */
    protected function configureOptions(OptionsResolverInterface $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults(array(
            'authorization_url'   => 'https://www.facebook.com/v2.0/dialog/oauth',
            'access_token_url'    => 'https://graph.facebook.com/v2.0/oauth/access_token',
            'revoke_token_url'    => 'https://graph.facebook.com/v2.0/me/permissions',
            'infos_url'           => 'https://graph.facebook.com/v2.0/me',

            'use_commas_in_scope' => true,

            'display'             => null,
            'auth_type'           => null,
        ));

        // Symfony <2.6 BC
        if (method_exists($resolver, 'setDefined')) {
            $resolver
                ->setAllowedValues('display', array('page', 'popup', 'touch', null)) // @link https://developers.facebook.com/docs/reference/dialogs/#display
                ->setAllowedValues('auth_type', array('rerequest', null)) // @link https://developers.facebook.com/docs/reference/javascript/FB.login/
            ;
        } else {
            $resolver->setAllowedValues(array(
                'display'   => array('page', 'popup', 'touch', null),
                'auth_type' => array('rerequest', null),
            ));
        }
    }
}
