## Intro

Here's my attempt at this tech challenge. I didn't have too much trouble with the min/max/average/median parts but
more issue with the second art - finding the outliers. I took a guess at what the "underpeforming" criteria in order to
pass the test suite but it wouldn't hold up to much scrutiny and it wouldn't work for multiple sets of underprforming 
date ranges.

I had trouble with getting docker to work but this may be more to do with my laptop which is very old and is giving me 
trouble lately. Instead, I just got the tests running locally without using docker.

I ran well over the 1.5 hours suggested. I'm not sure how this reflects on me or the challenge, but my gut feeling was
that this is more than 1.5 hours work to do cleanly and with good test coverage.

## Things to improve

- Test suite could be better, with more coverage and tests for bad files, missing files, poor data structure etc.
- More abstraction of maths parts into a separate service/helper class
- Better solution to underperforming date range algorithm.
- Extract reporting template into a file. It's small, so I didn't particularly feel the need for this test