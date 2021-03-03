#!/usr/bin/env node
'use strict';

const puppeteer = require('puppeteer');

(async () => {
    const options = parseOptions();
    const browser = await puppeteer.launch({
        headless : options.headless,
        args : [ '--start-maximized', '--no-sandbox']
    });

    const pages = await browser.pages();
    const page = pages[0];
    await page.setViewport({width: 1920, height: 1080});
    const waitOptions = {waitUntil: ['networkidle2', 'domcontentloaded', 'load']};

    if (options.post) {
        await page.setContent(generateForm(options.loginUrl, 'post'));
        const inputElement = await page.$('input[type=submit]');
        await inputElement.click();
    } else {
        await page.goto(options.loginUrl);
    }

    await page.waitForNavigation(waitOptions);
    await page.type('#login', options.mobileOrEmail);
    await page.type('#password', options.password);

    await page.setRequestInterception(true);
    page.on('request', request => {
        if (request.url().startsWith(options.redirectUrlPrefix)) {
            console.log(request.url());
            request.abort();
            return;
        }
        console.error('Requesting '+request.url());
        request.continue();
    });
    page.on('response', response => {
        console.error('Response from '+response.url());
        console.error(response.headers());
        if (response.headers()['content-type'] != 'text/html') {
            return;
        }
        response.text().then(result => {
            console.error('Body from '+response.url());
            console.error(result);
        }, e => {
            // ignore
        });
    });

    await Promise.all([
        page.waitForNavigation(waitOptions),
        page.click('button[data-bind="click: loginByPwd"]'),
    ]);

    const grantAuthorization = await page.$('#grantAuthorization');
    if (null != grantAuthorization) {
        await Promise.all([
            page.waitForNavigation(waitOptions),
            page.click('#grantAuthorization'),
        ]);
    }

    await browser.close();
})();

function generateForm(url, method) {
    const [action, parameters] = url.split('?')

    let inputs = parameters.split('&').map(function (encodedValue) {
        const [name, value] = encodedValue.split('=').map(decodeURIComponent);

        return `<input type="hidden" name="${name}" value="${value}" />`;
    }).join('\n            ');

    return `
        <form action="${action}" method="${method}">
            ${inputs}
            <input type="submit" />
        </form>
    `;
};

function parseOptions() {
    const args = require('command-line-args');
    const usage = require('command-line-usage');

    const optionDefinitions = [ {
        name : 'help',
        alias : 'h',
        type : Boolean,
        description : 'Display this usage guide.'
    }, {
        name : 'mobileOrEmail',
        alias : 'm',
        type : String,
        description : 'Mobile or email of user'
    }, {
        name : 'password',
        alias : 'p',
        type : String,
        description : 'Password of user'
    }, {
        name : 'loginUrl',
        type : String,
        description : 'Login URL'
    }, {
        name : 'redirectUrlPrefix',
        type : String,
        description : 'Redirect URL prefix to which ESIA redirects and which will be printed to STDOUT'
    }, {
        name: 'headless',
        defaultValue: false,
        type: Boolean,
        description : 'Should we start chrome in headless mode?'
    }, {
        name: 'post',
        defaultValue: false,
        type: Boolean,
        description : 'Should we use POST method for login URL?'
    }];
    const options = args(optionDefinitions);

    if (options.help) {
        const usageString = usage([
                {
                    header : 'Authentication bot',
                    content : 'Logs in to ESIA URL by provided login/password and dumps out redirection URL.'
                }, {
                    header : 'Options',
                    optionList : optionDefinitions
                } ]);
        console.error(usageString);
        process.exit(1);
    }

    return options;
};