<?php

namespace markhuot\craftpest\dom;

use markhuot\craftpest\http\RequestBuilder;

/**
 * A `NodeList` represents a fragment of HTML. It can contain one or more nodes and
 * the return values of its methods vary based on the count. For example getting the text
 * of a single h1 element via `$response->querySelector('h1')->text === "string"` will return the string
 * contents of that node. However, if the `NodeList` contains multiple nodes the return
 * will be an array such as when you get back multiple list items, `$response->querySelector('li')->text === ["list", "text", "items"]`
 *
 * @property int $count
 */
class NodeList implements \Countable
{
    /** @var \Symfony\Component\DomCrawler\Crawler */
    public $crawler;

    function __construct(\Symfony\Component\DomCrawler\Crawler $crawler) {
        $this->crawler = $crawler;
    }

    /**
     * You can turn any `NodeList` in to an expectation API by calling `->expect()` on it. From there
     * you are free to use the expectation API to assert the DOM matches your expectations.
     * 
     * ```php
     * $response->querySelector('li')->expect()->count->toBe(10);
     * ```
     */
    function expect()
    {
        return test()->expect($this);
    }

    /**
     * Allows access to the getText() and getInnerHTML() methods via magic properties
     * so a NodeList can be expected.
     *
     * ```php
     * expect($nodeList)->text->toBe('some text content');
     * ```
     *
     * @internal
     */
    function __get($property) {
        $getter = 'get' . ucfirst($property);

        if (method_exists($this, $getter)) {
            return $this->{$getter}();
        }

        throw new \Exception("Property `{$property}` not found on Pest\\CraftCms\\NodeList");
    }

    /**
     * A poorly named map that either returns the result of the map on
     * a single node or an array of mapped values on multiple nodes.
     *
     * This is called internally when you `__get` on a node list.
     *
     * If `$nodeList` contains 1 node, you'll get back the text content
     * of that node.
     * ```php
     * $textContent = $nodeList->getNodeOrNodes(fn ($node) => $node->text()); // string
     * ```
     *
     * If `$nodeList` contains 2 or more nodes, you'll get back an array
     * containing the text content of each node.
     * ```php
     * $textContent = $nodeList->getNodeOrNodes(fn ($node) => $node->text()); // array
     * ```
     */
    public function getNodeOrNodes(callable $callback) {
        if ($this->crawler->count() === 1) {
            return $callback($this->crawler->eq(0));
        }

        $result = [];
        for ($i=0; $i<$this->crawler->count(); $i++) {
            $node = $this->crawler->eq($i);
            $result[] = $callback($node);
        }

        return $result;
    }

    /**
     * Available as a method or a magic property of `->text`. Gets the text content of the node or nodes. This
     * will only return the text content of the node as well as any child nodes. Any non-text content such as
     * HTML tags will be removed.
     */
    function getText(): array|string {
        return $this->getNodeOrNodes(fn ($node) => $node->text());
    }

    /**
     * Available as a method or a magic property of `->innerHTML`. Gets the inner HTML of the node or nodes.
     */
    public function getInnerHTML(): array|string  {
        return $this->getNodeOrNodes(fn ($node) => $node->html());
    }

    /**
     * The number of nodes within the node list. Used for `\Countable` purposes. Most
     * access would be through the `getCount()` method.
     * @internal
     */
    public function count(): int {
        return $this->crawler->count();
    }

    /**
     * Available via the method or a magic property of `->count` returns
     * the number of nodes in the node list.
     */
    public function getCount(): int {
        return $this->count();
    }

    /**
     * Click the matched element and follow a link.
     * 
     * ```php
     * $response->querySelector('a')->click();
     * ```
     */
    function click()
    {
        $node = $this->crawler->first();
        $nodeName = $node->nodeName();

        if ($nodeName === 'a') {
            $href = $node->attr('href');
            return (new RequestBuilder('get', $href))->send();
        }

        throw new \Exception('Not able to interact with `' . $nodeName . '` elements.');
    }

    /**
     * Asserts that the given string matches the text content of the node list.
     *
     * Caution: if the node list contains multiple nodes then the assertion
     * would expect an array of strings to match.
     *
     * ```php
     * $nodeList->assertText('Hello World');
     * ```
     */
    public function assertText($expected) {
        test()->assertSame($expected, $this->getText());

        return $this;
    }

    /**
     * Asserts that the given string is a part of the node list text content
     *
     * ```php
     * $nodeList->assertContainsString('Hello');
     * ```
     */
    public function assertContainsString($expected) {
        test()->assertStringContainsString($expected, $this->getText());

        return $this;
    }

    /**
     * Asserts that the given count matches the count of nodes in the node list.
     *
     * ```php
     * $nodeList->assertCount(2);
     * ```
     */
    public function assertCount($expected) {
        test()->assertCount($expected, $this);

        return $this;
    }
}
