<?php
namespace Plugins\Api;
use Exception;
use Core\Response;

class Api
{

	protected $app;
	protected $config;
	
	function __construct($app)
	{
		$this->app = $app;
		// load plugin configuration.
		$this->config = get_plugin_config($this);

		// check if the prefix is defined.
		if ( ! array_get(get_object_vars($this->config), 'url') ) {
			throw new Exception("URL is not defined.");
		}

		$this->app->event->on('before.routes', [$this, 'setup']);
	}

	/**
	 * Setup routes.
	 * @param  array &$routes
	 * @return
	 */
	public function setup(&$routes)
	{
		// hook all api urls
		$routes[$this->config->url . '(.*)'] = [$this, 'dispatcher'];

		// by pass the index page.
		$routes[''] = function () { return ''; };

		// 404 all other pages.
		$routes['(.*)'] = [$this, 'disableNoneApiCalls'];
	}

	/**
	 * Short circuit none api calls
	 */
	public function disableNoneApiCalls()
	{
		$response = new Response;
		exit($response->redirect('/'));
	}

	/**
	 * Dispatching the api call
	 * @return string
	 */
	public function dispatcher()
	{
		$this->app->event->on('before.render', [$this, 'response']);
		return preg_replace('~^' . preg_quote($this->config->url, '/') . '~', '', $this->app->request->uri());
	}

	/**
	 * Api response as json.
	 * render response or return 404 not found.
	 * @return string
	 */
	function response() {
		$data = $this->app->page->header();
		$data['content'] = $this->app->page->content();

		$response = new Response;

		if (array_get($data, 'layout') == '404')
		{
			$data = ['error' => 'page not found', 'status' => 404];
			$response->status(404);
		}

		$response->header('Content-Type', 'application/json')
				->content(json_encode($data));
		exit($response);
	}
}