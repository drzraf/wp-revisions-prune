drzraf/wp-revisions-prune
========================

Prune revisions

Quick links: [Using](#using) | [Installing](#installing) | [Contributing](#contributing) | [Support](#support)

## Using

This package implements the following commands:

### wp revisions prune

Prune a list of revisions

This command does not touch the database (not even attempt to read from it). Instead, it acts as a filter after a CSV list
(in the following format: `<ID>, <post_name>, <post_date>`) and returns a pruned list of lines (or raw post ID) according to the
pruning "policy" specified in on the command line.

The CSV can be generated using:
- `wp post list --post_type=revision --format=csv --fields=ID,post_name,post_date_gmt`
- `wp revisions list --format=csv --fields=ID,post_name,post_date_gmt --yes`

~~~
wp revisions prune [--file=<file>] [--post_id=<post-id>] [--fields=<fields>] [--yes] [--format=<format>] [--file=<file>] [--keep-last=<number>] [--keep-hourly=<number>] [--keep-daily=<number>] [--keep-weekly=<number>] [--keep-monthly=<number>] [--keep-yearly=<number>] [--keep-less-than-n-rev=<number>] [--keep-before=<yyyy-mm-dd>] [--keep-after=<yyyy-mm-dd>] [--list]
~~~

**OPTIONS**

	[--file=<file>]
	: Use input CSV file. (stdin otherwise or if <file> does not exists)

	[--keep-last=<number>]
	: Keep at least <number> revisions.

	[--keep-hourly=<number>]
	: Number of hourly revisions to keep.

	[--keep-daily=<number>]
	: Number of daily revisions to keep.

	[--keep-weekly=<number>]
	: Number of weekly revisions to keep.

	[--keep-monthly=<number>]
	: Number of monthly revisions to keep.

	[--keep-yearly=<number>]
	: Number of yearly revisions to keep.

	[--keep-less-than-n-rev=<number>]
	: Disregard pruning revisions of posts having less than <number> revisions.

	[--keep-before=<yyyy-mm-dd>]
	: Keep revisions published on or before this date.

	[--keep-after=<yyyy-mm-dd>]
	: Keep revisions published on or after this date.

	[--list]
	: Output verbose list of revisions it keeps/prunes
	With --list=removed only output the flat list of post ID to remove.

**EXAMPLES**

    wp revisions list --format=csv --fields=ID,post_name,post_date_gmt --yes | wp revisions prune --keep-daily=1 --list
    wp revisions list --format=csv --fields=ID,post_name,post_date_gmt --yes | wp revisions prune --keep-hourly=2 --list=removed


## Installing

Installing this package requires WP-CLI v2.1 or greater. Update to the latest stable release with `wp cli update`.

Once you've done so, you can install this package with:

    wp package install git@github.com:drzraf/wp-revisions-prune.git

## Contributing

We appreciate you taking the initiative to contribute to this project.

Contributing isn’t limited to just code. We encourage you to contribute in the way that best fits your abilities, by writing tutorials, giving a demo at your local meetup, helping other users with their support questions, or revising our documentation.

For a more thorough introduction, [check out WP-CLI's guide to contributing](https://make.wordpress.org/cli/handbook/contributing/). This package follows those policy and guidelines.

### Reporting a bug

Think you’ve found a bug? We’d love for you to help us get it fixed.

Before you create a new issue, you should [search existing issues](https://github.com/drzraf/wp-revisions-prune/issues?q=label%3Abug%20) to see if there’s an existing resolution to it, or if it’s already been fixed in a newer version.

Once you’ve done a bit of searching and discovered there isn’t an open or fixed issue for your bug, please [create a new issue](https://github.com/drzraf/wp-revisions-prune/issues/new). Include as much detail as you can, and clear steps to reproduce if possible. For more guidance, [review our bug report documentation](https://make.wordpress.org/cli/handbook/bug-reports/).

### Creating a pull request

Want to contribute a new feature? Please first [open a new issue](https://github.com/drzraf/wp-revisions-prune/issues/new) to discuss whether the feature is a good fit for the project.

Once you've decided to commit the time to seeing your pull request through, [please follow our guidelines for creating a pull request](https://make.wordpress.org/cli/handbook/pull-requests/) to make sure it's a pleasant experience. See "[Setting up](https://make.wordpress.org/cli/handbook/pull-requests/#setting-up)" for details specific to working on this package locally.

## Support

Github issues aren't for general support questions, but there are other venues you can try: https://wp-cli.org/#support


*This README.md is generated dynamically from the project's codebase using `wp scaffold package-readme` ([doc](https://github.com/wp-cli/scaffold-package-command#wp-scaffold-package-readme)). To suggest changes, please submit a pull request against the corresponding part of the codebase.*
