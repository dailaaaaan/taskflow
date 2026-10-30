import { expect, test } from '@playwright/test';
import type { Browser, Page } from '@playwright/test';

/** Signs in with a seeded account, in its own browser context, and opens the board. */
async function openBoardAs(browser: Browser, email: string): Promise<Page> {
    const context = await browser.newContext();
    const page = await context.newPage();

    await page.goto('/login');
    await page.locator('[name=email]').fill(email);
    await page.locator('[name=password]').fill('password');
    await page.getByTestId('login-button').click();
    await page.getByRole('link', { name: 'Acme Product Team' }).click();
    await page.getByRole('link', { name: 'Product Roadmap' }).click();
    await expect(
        page.getByRole('heading', { name: 'Product Roadmap', level: 1 }),
    ).toBeVisible();

    return page;
}

function list(page: Page, title: string) {
    return page.getByRole('region', { name: title });
}

/**
 * Presses a key on the drag handle's page, then waits for its screen reader
 * announcement and for dnd-kit to measure the new layout: real keys arrive
 * much faster than React renders.
 */
async function press(page: Page, key: string, announcement: string) {
    await page.keyboard.press(key);
    await expect(page.locator('[id^=DndLiveRegion]')).toContainText(
        announcement,
    );
    await page.evaluate(
        () =>
            new Promise((resolve) =>
                requestAnimationFrame(() => requestAnimationFrame(resolve)),
            ),
    );
}

test('two members work on the same board live', async ({ browser }) => {
    const demo = await openBoardAs(browser, 'demo@taskflow.test');
    const sam = await openBoardAs(browser, 'sam@taskflow.test');

    await test.step('each one sees the other on the board', async () => {
        await expect(demo.getByTestId('board-viewers')).toHaveAttribute(
            'aria-label',
            'Also viewing: Sam Taylor',
        );
        await expect(sam.getByTestId('board-viewers')).toHaveAttribute(
            'aria-label',
            'Also viewing: Demo User',
        );
    });

    await test.step('a new card reaches the other member', async () => {
        const todo = list(demo, 'To do');
        const request = demo.waitForRequest(
            (candidate) =>
                candidate.method() === 'POST' &&
                candidate.url().endsWith('/cards'),
        );

        await todo.getByTestId('add-card-button').click();
        await todo.getByTestId('new-card-title').fill('Live card');
        await todo.getByTestId('submit-card-button').click();

        // Without this header the author would get an echo of their own change.
        expect((await request).headers()['x-socket-id']).toMatch(/^\d+\.\d+$/);
        await expect(list(sam, 'To do').getByText('Live card')).toBeVisible();
    });

    await test.step('a moved card reaches the other member', async () => {
        await sam.getByRole('button', { name: 'Move card Live card' }).focus();
        await press(sam, 'Space', 'Picked up card “Live card”');
        await press(sam, 'ArrowRight', 'in list “In progress”.');
        await press(sam, 'Space', 'Dropped card “Live card”');

        await expect(
            list(demo, 'In progress').getByText('Live card'),
        ).toBeVisible();
        await expect(list(demo, 'To do').getByText('Live card')).toHaveCount(0);
    });

    await test.step('edited details reach the other member', async () => {
        await demo.getByText('Live card').click();
        await demo.locator('#card-title').fill('Edited card');
        await demo.getByTestId('save-card-button').click();

        await expect(
            list(sam, 'In progress').getByText('Edited card'),
        ).toBeVisible();
    });

    await test.step('a new and renamed list reach the other member', async () => {
        await demo.getByTestId('add-list-button').click();
        await demo.getByTestId('new-list-title').fill('Review');
        await demo.getByTestId('submit-list-button').click();
        await expect(list(sam, 'Review')).toBeVisible();

        await sam.getByRole('button', { name: 'Rename list Review' }).click();
        await sam.getByRole('textbox', { name: 'List title' }).fill('QA');
        await sam.getByRole('button', { name: 'Save' }).click();
        await expect(list(demo, 'QA')).toBeVisible();
    });

    await test.step('a deleted card disappears for the other member', async () => {
        await sam.getByText('Edited card').click();
        await sam.getByTestId('delete-card-button').click();
        await sam.getByTestId('confirm-button').click();

        await expect(demo.getByText('Edited card')).toHaveCount(0);
    });

    await test.step('leaving the board removes the avatar', async () => {
        await sam.close();

        await expect(demo.getByTestId('board-viewers')).toHaveCount(0);
    });
});
