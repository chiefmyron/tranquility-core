<?php

/**
 * Enumeration of telephone address sub-types:
 *   - Home
 *   - Mobile
 *   - Work
 *   - Company
 *   - Pager
 *   - Work fax
 *   - Home fax
 *
 * @package \Tranquility\Enum\Address
 * @author Andrew Patterson <patto@live.com.au>
 */

namespace Tranquility\Enum\Address;

class SubtypePhone extends \Tranquility\Enum {
    
    const Home = 'home';
    const Mobile = 'mobile';
    const Work = 'work';
    const Company = 'company';
    const Pager = 'pager';
    const WorkFax = 'workFax';
    const HomeFax = 'homeFax';
}