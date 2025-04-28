'use client';

import React from 'react';
import {DocsThemeConfig, useConfig} from 'nextra-theme-docs';
import {useRouter} from 'next/router';

const config: DocsThemeConfig = {
    logo: (
      <>
          <svg
            width='24'
            height='24'
            viewBox='0 0 48.7 48.7'
            xmlns='http://www.w3.org/2000/svg'
          >
              <path
                d='M0,24.35C0,16.8,0,13,1.22,10A16,16,0,0,1,10,1.22C13,0,16.8,0,24.35,0h0C31.9,0,35.71,0,38.72,1.22A16,16,0,0,1,47.48,10c1.22,3,1.22,6.82,1.22,14.37h0c0,7.55,0,11.36-1.22,14.37a16,16,0,0,1-8.76,8.76c-3,1.22-6.82,1.22-14.37,1.22h0C16.8,48.7,13,48.7,10,47.48a16,16,0,0,1-8.76-8.76C0,35.71,0,31.9,0,24.35Z'
                fill='#7c3aed'
              />
              <path
                d='M36.12,25.81,28.9,13.23h0a3.59,3.59,0,0,0-6.25,0l-11,19.16a2.59,2.59,0,0,0-.33,1.38A2.72,2.72,0,0,0,14,36.53a2.64,2.64,0,0,0,2.36-1.38L25.08,20a.84.84,0,0,1,1.46,0l2.76,4.79a.72.72,0,0,1-.65,1H25.57a2.72,2.72,0,0,0-2.68,2.76h0a2.72,2.72,0,0,0,2.68,2.76h9.17a2.63,2.63,0,0,0,2.27-4Z'
                fill='#fff'
              />
          </svg>
          <span style={{marginLeft: '.4em', fontWeight: 800, color: 'inherit'}}>
         FOLLOWABLE
      </span>
      </>
    ),

    project: {
        link: 'https://github.com/akira-io/laravel-followable',
    },

    chat: {
        link: 'https://discord.gg/ghPqZg3RcZ',
    },

    docsRepositoryBase: 'https://github.com/akira-io/laravel-followable/tree/main/docs',

    footer: {
        text: `Powered by Akira Corporation © ${new Date().getFullYear()}`,
    },

    primaryHue: {
        light: 262,
        dark: 268,
    },

    editLink: {
        component: null,
    },

    useNextSeoProps() {
        return {
            titleTemplate: '%s | Followable',
        };
    },

    head: () => {
        const {asPath, defaultLocale, locale} = useRouter();
        const {frontMatter} = useConfig();
        const url = `https://followable.akira-io.com${defaultLocale === locale ? asPath : `/${locale}${asPath}`}`.replace(/\/$/, '') + '/';

        return (
          <>
              <meta property='og:url' content={url}/>
              <meta property='og:title' content={frontMatter.title || 'Followable'}/>
              <link rel='icon' href='/favicon.ico'/>
              <meta
                property='og:description'
                content={frontMatter.description || 'Laravel Followable Documentation'}
              />
          </>
        );
    },
};

export default config;