from io import BytesIO

import imagehash
import requests
from PIL import Image
from pydash import find, get, map_, group_by, uniq_by, order_by, flatten, omit, clone_deep


class ResponseAdapter:

    EMPTY_RESPONSE = {'images': [], 'urls': []}
    CATEGORY_TYPES = ['instagram', 'vk', 'drom', 'avito', 'drive2']
    MAX_IMAGES = 5
    MAX_URLS = 5

    _hash_images_map = {}
    result = clone_deep(EMPTY_RESPONSE)

    def cast(self, response):
        self._hash_images_map = {}
        self.result = clone_deep(ResponseAdapter.EMPTY_RESPONSE)

        for group in get(response, 'data.groups', []):
            if not self._is_enough_images():
                self._add_images(group)
            self._add_urls(group)

        self._postprocess_urls()
        self._postprocess_images()
        return self.result

    def _postprocess_images(self):
        output = []
        for record in self.result['images']:
            for image in get(record, 'images'):
                output.append({**omit(record, 'images'), 'image_url': image})
        self.result['images'] = output

    def _postprocess_urls(self):
        grouped_urls = group_by(self.result['urls'], 'group_category')

        def pick_url(v):
            output, duplicated = [], []
            for u in order_by(uniq_by(v, 'group_url'), ['group_description'], reverse=True):
                desc = u['group_description']
                if desc not in duplicated:
                    if desc:
                        duplicated.append(desc)
                    output.append(u)

            return output[:ResponseAdapter.MAX_URLS]

        self.result['urls'] = flatten([pick_url(v) for v in grouped_urls.values()])

    def _add_images(self, group):
        city_name, region_name, date = get(group, 'city_name'), get(group, 'region_name'), get(group, 'date')
        images = map_(get(group, 'photos'), lambda i: get(i, 'src.default'))
        images = self._pick_images_by_hash(images)

        if images:
            self.result['images'].append({
                'image_city': city_name,
                'image_region': region_name,
                'image_date': date,
                'images': images
            })

    def _add_urls(self, group):
        group_url, description = get(group, 'group_url'), get(group, 'description')

        if group_url:
            self.result['urls'].append({
                'group_url': group_url,
                'group_description': description,
                'group_category': ResponseAdapter.get_category_by_url(group_url)
            })

    def _is_enough_images(self):
        return len(self._hash_images_map.keys()) >= ResponseAdapter.MAX_IMAGES

    def _pick_images_by_hash(self, images):
        output = []
        for url in images:
            if self._is_enough_images():
                break
            try:
                r = requests.get(url)
            except Exception:
                continue

            image_hash = imagehash.average_hash(Image.open(BytesIO(r.content)))
            if image_hash not in self._hash_images_map:
                self._hash_images_map[image_hash] = url
                output.append(url)
        return output

    @staticmethod
    def get_category_by_url(url):
        category = find(ResponseAdapter.CATEGORY_TYPES, lambda c: c in url)
        return category if category else 'other'
