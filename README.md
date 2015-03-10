# Find & Replace for wp-cli
Find and replace in many places within wordpress, available are post content, excerpts, links, attachments, custom fields, guids.
Based on [Velvet Blues Update URLs](https://wordpress.org/plugins/velvet-blues-update-urls/) plugin by VelvetBlues.com

### Dependancies
* Wordpress
* wp-cli : http://wp-cli.org

### Installation
See https://github.com/wp-cli/wp-cli/wiki/Community-Packages#installing-a-package-without-composer 

### Usage

#### Options

<find>
: The string in find. (required)

<replace>
: New string to replace. (required)

[<location>]
: Locations to find and replace, defaults to just post content if none are specified. (optional)

#### Flags
--all 
: Overrides location args, will replace in all available locations.

--all-but-guids
: Overrides location args, will replace in all available locations except guids

#### Examples

wp replace http://oldurl.com/ http://newurl.com content excerpts custom

wp replace http://oldurl.com/ http://newurl.com --all