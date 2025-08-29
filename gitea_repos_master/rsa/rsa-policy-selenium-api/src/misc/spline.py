import random

import numpy as np
import scipy.interpolate as si


class SplineGenerator:
    @staticmethod
    def generate_points():
        points = [[random.randint(-2, 5), random.randint(-2, 5)] for _ in range(10)]
        points = np.array(points)

        x = points[:, 0]
        y = points[:, 1]

        t = range(len(points))
        ipl_t = np.linspace(0.0, len(points) - 1, 100)

        x_tup = si.splrep(t, x, k=3)
        y_tup = si.splrep(t, y, k=3)

        x_list = list(x_tup)
        xl = x.tolist()
        x_list[1] = xl + [0.0, 0.0, 0.0, 0.0]

        y_list = list(y_tup)
        yl = y.tolist()
        y_list[1] = yl + [0.0, 0.0, 0.0, 0.0]

        x_i = si.splev(ipl_t, x_list)  # x interpolate values
        y_i = si.splev(ipl_t, y_list)  # y interpolate values

        return x_i, y_i

    @staticmethod
    def generate_offsets(size=50):
        points_x, points_y = SplineGenerator.generate_points()
        output_x, output_y = [], []

        for i in range(1, len(points_x)):
            output_x.append(-size * (points_x[i] - points_x[i - 1]))
            output_y.append(-size * (points_y[i] - points_y[i - 1]))

        return output_x, output_y
