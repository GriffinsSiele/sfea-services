from src.utils.utils import random_float


class ViewpointManager:
    @staticmethod
    def generate_random(viewpoint1=None, viewpoint2=None, as_string=False):
        v1 = viewpoint1 if viewpoint1 else [random_float(30, 50), random_float(30, 50)]
        v2 = (
            viewpoint2
            if viewpoint2
            else [v1[0] + random_float(0, 10), v1[1] - random_float(0, 10)]
        )

        if not as_string:
            return v1, v2

        def to_str(v):
            return ", ".join([str(e) for e in v])

        return to_str(v1), to_str(v2)

    @staticmethod
    def coordinates_to_rect(coord, distance=1):
        if not coord:
            return None, None
        v1 = [coord[0] - distance, coord[1] + distance]
        v2 = [coord[0] + distance, coord[1] - distance]
        return v1, v2
