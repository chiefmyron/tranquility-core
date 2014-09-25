<?php

/**
 * Enumeration of publishing states for content items:
 *   - Draft
 *   - Published
 *   - PublishedTimed
 *
 * @package \Tranquility\Enum\Content
 * @author Andrew Patterson <patto@live.com.au>
 */

namespace Tranquility\Enum\Content;

class PublishState extends \Tranquility\Enum {
    
    const Draft = 'draft';
    const Published = 'published';
    const PublishedTimed = 'timed';
    
}