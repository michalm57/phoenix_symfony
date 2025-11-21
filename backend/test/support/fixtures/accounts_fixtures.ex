defmodule App.AccountsFixtures do
  @moduledoc """
  This module defines test helpers for creating
  entities via the `App.Accounts` context.
  """

  @doc """
  Generate a user.
  """
  def user_fixture(attrs \\ %{}) do
    {:ok, user} =
      attrs
      |> Enum.into(%{
        birthdate: ~D[2025-11-18],
        first_name: "some first_name",
        gender: "some gender",
        last_name: "some last_name"
      })
      |> App.Accounts.create_user()

    user
  end
end
