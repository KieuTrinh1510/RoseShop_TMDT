// lib/widgets/hoa_don/skeleton_hoa_don.dart
// Skeleton loading cho màn chi tiết hóa đơn.
import 'package:flutter/material.dart';
import '../common/glass_container.dart';
import '../common/skeleton.dart';

class OrderDetailSkeleton extends StatelessWidget {
  const OrderDetailSkeleton({super.key});

  @override
  Widget build(BuildContext context) {
    return SkeletonShimmer(
      child: SingleChildScrollView(
        physics: const NeverScrollableScrollPhysics(),
        padding: EdgeInsets.fromLTRB(
          20,
          8,
          20,
          32 + MediaQuery.of(context).padding.bottom,
        ),
        child: const Column(
          children: [
            // Summary
            GlassContainer(
              blur: 0,
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      SkeletonBox(width: 140, height: 26, radius: 10),
                      Spacer(),
                      SkeletonBox(width: 76, height: 24, radius: 12),
                    ],
                  ),
                  SizedBox(height: 12),
                  Row(
                    children: [
                      SkeletonBox(width: 52, height: 22, radius: 11),
                      SizedBox(width: 6),
                      SkeletonBox(width: 90, height: 22, radius: 11),
                    ],
                  ),
                ],
              ),
            ),
            SizedBox(height: 14),
            // Progress
            GlassContainer(
              blur: 0,
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  SkeletonBox(width: 150, height: 18),
                  SizedBox(height: 16),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      SkeletonBox(width: 34, height: 34, radius: 17),
                      SkeletonBox(width: 34, height: 34, radius: 17),
                      SkeletonBox(width: 34, height: 34, radius: 17),
                      SkeletonBox(width: 34, height: 34, radius: 17),
                    ],
                  ),
                  SizedBox(height: 18),
                  SkeletonBox(width: double.infinity, height: 50, radius: 18),
                ],
              ),
            ),
            SizedBox(height: 14),
            // Customer
            GlassContainer(
              blur: 0,
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  SkeletonBox(width: 170, height: 18),
                  SizedBox(height: 16),
                  SkeletonBox(width: double.infinity, height: 14),
                  SizedBox(height: 14),
                  SkeletonBox(width: double.infinity, height: 14),
                  SizedBox(height: 14),
                  SkeletonBox(width: double.infinity, height: 14),
                ],
              ),
            ),
            SizedBox(height: 14),
            // Products
            GlassContainer(
              blur: 0,
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  SkeletonBox(width: 150, height: 18),
                  SizedBox(height: 16),
                  Row(
                    children: [
                      SkeletonBox(width: 40, height: 40, radius: 20),
                      SizedBox(width: 12),
                      Expanded(child: SkeletonBox(height: 16)),
                      SizedBox(width: 12),
                      SkeletonBox(width: 70, height: 16),
                    ],
                  ),
                  SizedBox(height: 18),
                  SkeletonBox(width: double.infinity, height: 14),
                  SizedBox(height: 12),
                  SkeletonBox(width: double.infinity, height: 20),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}
