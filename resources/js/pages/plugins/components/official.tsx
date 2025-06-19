import { useInfiniteQuery } from '@tanstack/react-query';
import axios from 'axios';
import { Repo } from '@/types/repo';
import { BadgeCheckIcon, LoaderCircleIcon, StarIcon } from 'lucide-react';
import { CardRow } from '@/components/ui/card';
import React, { Fragment } from 'react';
import Install from '@/pages/plugins/components/install';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';

export default function OfficialPlugins() {
  const query = useInfiniteQuery<{
    total_count: number;
    incomplete_results: boolean;
    items: Repo[];
    next_page?: number;
  }>({
    queryKey: ['official-plugins'],
    queryFn: async ({ pageParam }) => {
      const data = (
        await axios.get('https://api.github.com/search/repositories?q=owner:vitodeploy%20topic:vitodeploy-plugin&per_page=10&page=' + pageParam)
      ).data;
      if (data.items.length == 10) {
        data.next_page = (pageParam as number) + 1;
      }
      return data;
    },
    retry: false,
    initialPageParam: 1,
    getNextPageParam: (lastPage) => lastPage.next_page,
  });

  return (
    <div>
      {query.isLoading ? (
        <CardRow className="items-center justify-center">
          <LoaderCircleIcon className="animate-spin" />
        </CardRow>
      ) : query.data && query.data.pages.length > 0 && query.data.pages[0].items.length > 0 ? (
        <>
          {query.data.pages.map((page) =>
            page.items.map((repo) => (
              <Fragment key={repo.id}>
                <CardRow key={repo.id}>
                  <div className="flex flex-col gap-1">
                    <div className="flex items-center gap-2">
                      <a href={repo.html_url} target="_blank" className="hover:text-primary">
                        {repo.name}
                      </a>
                      <BadgeCheckIcon className="text-primary size-4" />
                    </div>
                    <span className="text-muted-foreground text-xs">{repo.description}</span>
                  </div>
                  <div className="flex items-center gap-2">
                    <Button variant="outline" onClick={() => window.open(repo.html_url, '_blank')}>
                      <StarIcon />
                      {repo.stargazers_count}
                    </Button>
                    <Install repo={repo} />
                  </div>
                </CardRow>
                {!(page.items[page.items.length - 1].id === repo.id && page === query.data.pages[query.data.pages.length - 1]) && (
                  <Separator className="my-2" />
                )}
              </Fragment>
            )),
          )}
          {query.hasNextPage && (
            <div className="flex items-center justify-center p-5">
              <Button variant="outline" onClick={() => query.fetchNextPage()}>
                {query.isFetchingNextPage && <LoaderCircleIcon className="animate-spin" />}
                Load more
              </Button>
            </div>
          )}
        </>
      ) : (
        <CardRow className="items-center justify-center">
          <span className="text-muted-foreground">No plugins found</span>
        </CardRow>
      )}
    </div>
  );
}
