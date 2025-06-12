/**
 * atk_search.spec.js
 *
 * Search tests.
 *
 */

// Set up Playwright.
import { expect, test } from '@playwright/test'

/** ESLint directives */
/* eslint-disable import/first */

import * as atkCommands from '../support/atk_commands'
import * as atkUtilities from '../support/atk_utilities'

import playwrightConfig from '../../playwright.config'

const baseUrl = playwrightConfig.use.baseURL

// Import ATK Configuration.
import atkConfig from '../../playwright.atk.config' // eslint-disable-line no-unused-vars

// Standard accounts that use user accounts created
// by QA Accounts. QA Accounts are created when the QA
// Accounts module is enabled.
import qaUserAccounts from '../data/qaUsers.json'

// Search keywords and expected results.
// Adjust for your site.
const searchData = atkUtilities.readYAML('search.yml')

test.describe('Search tests.', () => {
  test('(ATK-PW-1160) Search content by a keyword. @ATK-PW-1160 @search @content', async ({ page }) => {
    // eslint-disable-next-line no-unused-vars
    const testId = 'ATK-PW-1160'

    await page.goto(baseUrl)

    const searchForm = page.getByLabel('Search Form')
    const isSearchFormVisible = await searchForm.isVisible()
    if (!isSearchFormVisible) {
      await page.getByLabel('Main Menu').click()
    }

    // eslint-disable-next-line no-restricted-syntax
    for (const item of searchData.simple) {
      // eslint-disable-next-line no-await-in-loop
      await atkCommands.openSearchForm(page)
      const keyInput = page.getByRole('searchbox', { name: 'Search' })
      // eslint-disable-next-line no-await-in-loop
      await keyInput.fill(item.keyword)
      // eslint-disable-next-line no-await-in-loop
      await keyInput.press('Enter')

      // Wait for search result to be shown.
      // eslint-disable-next-line no-await-in-loop
      await expect(page.getByText('Search results')).toBeVisible()

      // Check that expected items are shown.
      // eslint-disable-next-line no-await-in-loop
      await atkCommands.checkSearchResult(page, item)

      // Check that the search keyword(s) are highlighted in the text.
      // eslint-disable-next-line no-restricted-syntax
      for (const keyword of item.keyword.split(' ')) {
        // eslint-disable-next-line no-await-in-loop
        await expect(page.locator(`xpath=//strong[.="${keyword}"]`).first()).toBeVisible()
      }
    }
  })

  test('(ATK-PW-1161) Advanced search. @ATK-PW-1161 @search @content', async ({ page, context }) => {
    // eslint-disable-next-line no-unused-vars
    const testId = 'ATK-PW-1161'

    // eslint-disable-next-line no-restricted-syntax
    for (const item of searchData.advanced) {
      // In the default installation, only admin can do advanced search.
      // Change if it's configured different way on your site.
      // eslint-disable-next-line no-await-in-loop
      await atkCommands.logInViaForm(page, context, qaUserAccounts.admin)
      // eslint-disable-next-line no-await-in-loop
      await page.goto(`${baseUrl}search/node`)

      // Expand "Advanced search".
      // eslint-disable-next-line no-await-in-loop
      await page.getByRole('button', { name: 'Advanced search' }).click()

      // Fill all the configured data.
      if (item.any) {
        // eslint-disable-next-line no-await-in-loop
        await page.getByLabel('Containing any of the words').fill(item.any)
      }
      if (item.phrase) {
        // eslint-disable-next-line no-await-in-loop
        await page.getByLabel('Containing the phrase').fill(item.phrase)
      }
      if (item.none) {
        // eslint-disable-next-line no-await-in-loop
        await page.getByLabel('Containing none of the words').fill(item.none)
      }

      // Select node type if specified.
      // eslint-disable-next-line no-restricted-syntax
      for (const type of item.types) {
        // eslint-disable-next-line no-await-in-loop
        await page.getByRole('group', { name: 'Types' }).getByLabel(type).check()
      }

      // Select languages if specified.
      // eslint-disable-next-line no-restricted-syntax
      for (const language of item.languages) {
        // eslint-disable-next-line no-await-in-loop
        await page.getByRole('group', { name: 'Languages' }).getByLabel(language).check()
      }

      // eslint-disable-next-line no-await-in-loop
      await page.locator('input[value="Advanced search"]').click()

      // Wait for search result to be shown.
      // eslint-disable-next-line no-await-in-loop
      await atkCommands.checkSearchResult(page, item)
    }
  })

  test('(ATK-PW-1162) Search by a keyword: empty input @ATK-PW-1162 @search @content @empty', async ({ page }) => {
    // eslint-disable-next-line no-unused-vars
    const testId = 'ATK-PW-1162'

    await page.goto(baseUrl)

    const searchForm = page.getByLabel('Search Form')
    const isSearchFormVisible = await searchForm.isVisible()
    if (!isSearchFormVisible) {
      await page.getByLabel('Main Menu').click()
    }

    await atkCommands.openSearchForm(page)
    const searchInput = page.getByRole('searchbox', { name: 'Search' })
    await expect(searchInput).toHaveAttribute('placeholder', 'Search by keyword or phrase.')
  })

  test('(ATK-PW-1163) Advanced search: empty input @ATK-PW-1163 @search @content @empty', async ({ page, context }) => {
    // eslint-disable-next-line no-unused-vars
    const testId = 'ATK-PW-1163'

    // In the default installation, only admin can do advanced search.
    // Change if it's configured different way on your site.
    await atkCommands.logInViaForm(page, context, qaUserAccounts.admin)
    await page.goto(`${baseUrl}search/node`)

    // Expand "Advanced search".
    await page.getByRole('button', { name: 'Advanced search' }).click()

    await page.locator('input[value="Advanced search"]').click()

    // Wait for search result to be shown.
    await expect(page.getByText('Enter some keywords.')).toBeVisible()
  })
})
